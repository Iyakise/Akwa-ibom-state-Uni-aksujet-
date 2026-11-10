<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ManuscriptAuthor;
use App\Models\ManuscriptFile;
use App\Models\ManuscriptStatusHistory;
use App\Models\ManuscriptVersion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $manuscripts = Manuscript::query()->where('submitted_by', $request->user()->id)
            ->with(['journal', 'statusHistory'])
            ->orderByDesc('submitted_at')->paginate(12);

        return view('author.submissions.index', compact('manuscripts'));
    }

    public function create(): View
    {
        $journals = Journal::query()->where('status', 'active')->orderBy('title')->get();

        return view('author.submissions.create', compact('journals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_id' => ['required', 'integer', 'exists:journals,id'],
            'article_type' => ['required', 'string', 'in:research-article,review,case-study,short-communication'],
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'min:100', 'max:10000'],
            'cover_letter' => ['nullable', 'string', 'max:20000'],
            'author_name' => ['required', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'orcid' => ['nullable', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'coauthors' => ['nullable', 'string', 'max:10000'],
            'manuscript_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'confirm_originality' => ['accepted'],
            'confirm_ethics' => ['accepted'],
        ]);

        $journal = Journal::query()->where('status', 'active')->findOrFail($data['journal_id']);
        $authors = $this->parseCoauthors($data['coauthors'] ?? '');
        $file = $request->file('manuscript_file');
        $reference = $this->newReference();
        $safeOriginalName = Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 180, '');
        $storedPath = $file->storeAs("manuscripts/{$reference}", Str::uuid().'.'.$file->getClientOriginalExtension(), 'manuscripts');

        try {
            $manuscript = DB::transaction(function () use ($request, $data, $journal, $authors, $file, $reference, $safeOriginalName, $storedPath): Manuscript {
                /** @var User $user */
                $user = $request->user();
                $manuscript = Manuscript::query()->create([
                    'reference' => $reference,
                    'journal_id' => $journal->id,
                    'submitted_by' => $user->id,
                    'article_type' => $data['article_type'],
                    'title' => $data['title'],
                    'abstract' => $data['abstract'],
                    'cover_letter' => $data['cover_letter'] ?? null,
                    'declarations' => ['originality_confirmed' => true, 'ethics_confirmed' => true],
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

                $nameParts = preg_split('/\s+/', trim($data['author_name']), 2) ?: [];
                ManuscriptAuthor::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'user_id' => $user->id,
                    'position' => 1,
                    'given_name' => $nameParts[0] ?? $user->name,
                    'family_name' => $nameParts[1] ?? '(not provided)',
                    'email' => $user->email,
                    'affiliation' => $data['affiliation'] ?? $user->affiliation,
                    'orcid' => $data['orcid'] ?? $user->orcid,
                    'is_corresponding' => true,
                ]);

                foreach ($authors as $index => $author) {
                    ManuscriptAuthor::query()->create([
                        'manuscript_id' => $manuscript->id,
                        'position' => $index + 2,
                        ...$author,
                        'is_corresponding' => false,
                    ]);
                }

                $version = ManuscriptVersion::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'version_number' => 1,
                    'submitted_by' => $user->id,
                    'title' => $data['title'],
                    'abstract' => $data['abstract'],
                    'cover_letter' => $data['cover_letter'] ?? null,
                    'submitted_at' => now(),
                ]);

                ManuscriptFile::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'manuscript_version_id' => $version->id,
                    'uploaded_by' => $user->id,
                    'kind' => 'manuscript',
                    'disk' => 'manuscripts',
                    'path' => $storedPath,
                    'original_name' => $safeOriginalName,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ]);

                ManuscriptStatusHistory::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'changed_by' => $user->id,
                    'from_status' => null,
                    'to_status' => 'submitted',
                    'note' => 'Initial submission received.',
                    'created_at' => now(),
                ]);

                AuditLog::query()->create([
                    'actor_id' => $user->id,
                    'action' => 'manuscript.submitted',
                    'subject_type' => Manuscript::class,
                    'subject_id' => $manuscript->id,
                    'metadata' => ['reference' => $reference, 'journal_id' => $journal->id],
                    'ip_address' => request()->ip(),
                    'user_agent' => Str::limit((string) request()->userAgent(), 500),
                    'created_at' => now(),
                ]);

                return $manuscript;
            });
        } catch (Throwable $exception) {
            Storage::disk('manuscripts')->delete($storedPath);
            throw $exception;
        }

        return redirect()->route('author.submissions.show', $manuscript)->with('status', 'Your manuscript was submitted. Reference: '.$reference);
    }

    public function revision(Request $request, Manuscript $manuscript, \App\Services\ManuscriptWorkflowService $workflow): RedirectResponse
    {
        abort_unless($manuscript->submitted_by === $request->user()->id, 404);
        abort_unless($manuscript->status === 'revision_requested', 422, 'A revision can be submitted only when the editor requests one.');
        $data = $request->validate([
            'revision_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'response_to_reviewers' => ['required', 'string', 'min:20', 'max:20000'],
            'title' => ['nullable', 'string', 'max:500'],
            'abstract' => ['nullable', 'string', 'min:100', 'max:10000'],
        ]);
        $file = $request->file('revision_file');
        $storedPath = $file->storeAs("manuscripts/{$manuscript->reference}", \Illuminate\Support\Str::uuid().'.'.$file->getClientOriginalExtension(), 'manuscripts');

        try {
            DB::transaction(function () use ($request, $manuscript, $workflow, $data, $file, $storedPath): void {
                $locked = Manuscript::query()->lockForUpdate()->findOrFail($manuscript->id);
                abort_unless($locked->submitted_by === $request->user()->id && $locked->status === 'revision_requested', 422, 'The manuscript is no longer awaiting a revision.');
                $versionNumber = (int) $locked->versions()->lockForUpdate()->max('version_number') + 1;
                $title = $data['title'] ?: $locked->title;
                $abstract = $data['abstract'] ?: $locked->abstract;
                $version = ManuscriptVersion::query()->create([
                    'manuscript_id' => $locked->id,
                    'version_number' => $versionNumber,
                    'submitted_by' => $request->user()->id,
                    'title' => $title,
                    'abstract' => $abstract,
                    'author_response' => $data['response_to_reviewers'],
                    'submitted_at' => now(),
                ]);
                ManuscriptFile::query()->create([
                    'manuscript_id' => $locked->id,
                    'manuscript_version_id' => $version->id,
                    'uploaded_by' => $request->user()->id,
                    'kind' => 'revision',
                    'disk' => 'manuscripts',
                    'path' => $storedPath,
                    'original_name' => \Illuminate\Support\Str::limit(basename(str_replace('\\\\', '/', $file->getClientOriginalName())), 180, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ]);
                $locked->update(['title' => $title, 'abstract' => $abstract]);
                $workflow->transition($locked, \App\Enums\ManuscriptStatus::UnderReview, $request->user(), 'Author revision submitted; response to reviewers is attached to the private version record.');
                AuditLog::query()->create([
                    'actor_id' => $request->user()->id,
                    'action' => 'manuscript.revision_submitted',
                    'subject_type' => Manuscript::class,
                    'subject_id' => $locked->id,
                    'metadata' => ['reference' => $locked->reference, 'version' => $versionNumber],
                    'ip_address' => $request->ip(),
                    'user_agent' => \Illuminate\Support\Str::limit((string) $request->userAgent(), 500),
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('manuscripts')->delete($storedPath);
            throw $exception;
        }

        return redirect()->route('author.submissions.show', $manuscript)->with('status', 'Your revision was submitted to the editorial team.');
    }

    public function show(Request $request, Manuscript $manuscript): View
    {
        abort_unless($manuscript->submitted_by === $request->user()->id, 404);
        $manuscript->load(['journal', 'authors', 'versions.files', 'files', 'statusHistory.actor', 'decisions.editor']);

        return view('author.submissions.show', compact('manuscript'));
    }

    public function file(Request $request, ManuscriptFile $file)
    {
        abort_unless($file->manuscript()->where('submitted_by', $request->user()->id)->exists(), 404);
        $stream = Storage::disk('manuscripts')->readStream($file->path);
        abort_unless(is_resource($stream), 404);
        $name = rawurlencode(basename($file->original_name));

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => "attachment; filename=\"submission-file\"; filename*=UTF-8''{$name}",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @return list<array{given_name:string,family_name:string,email:string,affiliation:?string,orcid:?string}> */
    private function parseCoauthors(string $raw): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
        if (count($lines) > 19) {
            throw ValidationException::withMessages(['coauthors' => 'List no more than 19 additional authors.']);
        }

        $authors = [];
        foreach ($lines as $index => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2 || count($parts) > 4) {
                throw ValidationException::withMessages(['coauthors' => 'Each coauthor line must use: Full name | email | affiliation (optional) | ORCID (optional).']);
            }
            $name = preg_split('/\s+/', $parts[0], 2) ?: [];
            $candidate = [
                'given_name' => $name[0] ?? '',
                'family_name' => $name[1] ?? '',
                'email' => $parts[1] ?? '',
                'affiliation' => $parts[2] ?? null,
                'orcid' => $parts[3] ?? null,
            ];
            $validator = Validator::make($candidate, [
                'given_name' => ['required', 'string', 'max:120'],
                'family_name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'affiliation' => ['nullable', 'string', 'max:255'],
                'orcid' => ['nullable', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            ]);
            if ($validator->fails()) {
                throw ValidationException::withMessages(['coauthors' => 'Please check coauthor details on line '.($index + 1).'.']);
            }
            $authors[] = $validator->validated();
        }

        return $authors;
    }

    private function newReference(): string
    {
        do {
            $reference = 'AKS-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        } while (Manuscript::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
