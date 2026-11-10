<?php

namespace App\Http\Controllers\Editor;

use App\Enums\ManuscriptStatus;
use App\Http\Controllers\Controller;
use App\Mail\ManuscriptDecisionMail;
use App\Mail\ReviewInvitationMail;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleVersion;
use App\Models\AuditLog;
use App\Models\Correspondence;
use App\Models\EditorialDecision;
use App\Models\EditorAssignment;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ReviewAssignment;
use App\Models\ReviewRound;
use App\Models\User;
use App\Services\ManuscriptWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EditorialController extends Controller
{
    public function index(): View
    {
        $manuscripts = Manuscript::query()->with(['journal', 'submitter', 'editorAssignments.editor'])
            ->orderByRaw("CASE status WHEN 'submitted' THEN 0 WHEN 'initial_screening' THEN 1 WHEN 'revision_requested' THEN 2 WHEN 'under_review' THEN 3 ELSE 4 END")
            ->orderByDesc('submitted_at')->paginate(20);
        $summary = Manuscript::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $journals = Journal::query()->where('status', 'active')->orderBy('title')->get();
        $issues = Issue::query()->with('journal')->orderByDesc('publication_year')->orderByDesc('volume')->orderByDesc('issue_number')->get();

        return view('editor.dashboard', compact('manuscripts', 'summary', 'journals', 'issues'));
    }

    public function show(Manuscript $manuscript): View
    {
        $manuscript->load([
            'journal', 'submitter', 'authors', 'versions.files', 'files', 'statusHistory.actor',
            'editorAssignments.editor', 'reviewRounds.assignments.reviewer', 'reviewRounds.assignments.review',
            'decisions.editor', 'article.issue', 'article.files', 'article.versions',
        ]);
        $reviewers = User::query()->where('account_status', 'active')->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'reviewer'))->orderBy('name')->get();
        $editors = User::query()->where('account_status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['editor', 'administrator']))->orderBy('name')->get();
        $issues = Issue::query()->where('journal_id', $manuscript->journal_id)
            ->orderByDesc('publication_year')->orderByDesc('volume')->orderByDesc('issue_number')->get();

        return view('editor.manuscripts.show', compact('manuscript', 'reviewers', 'editors', 'issues'));
    }

    public function file(Manuscript $manuscript, \App\Models\ManuscriptFile $file)
    {
        abort_unless($file->manuscript_id === $manuscript->id && $file->disk === 'manuscripts', 404);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);
        $stream = $disk->readStream($file->path);
        abort_unless(is_resource($stream), 404);
        $name = rawurlencode(basename(str_replace('\\', '/', $file->original_name)));

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

    public function articleFile(Article $article, ArticleFile $file)
    {
        abort_unless($file->article_id === $article->id && $file->kind === 'full-text' && $file->disk === 'manuscripts', 404);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);
        $stream = $disk->readStream($file->path);
        abort_unless(is_resource($stream), 404);
        $name = rawurlencode(basename(str_replace('\\', '/', $file->original_name)));

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"article-proof.pdf\"; filename*=UTF-8''{$name}",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function transition(Request $request, Manuscript $manuscript, ManuscriptWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['screening', 'send_to_review', 'revision', 'accept', 'reject'])],
            'note' => ['nullable', 'string', 'max:5000'],
            'decision_letter' => ['nullable', 'string', 'max:20000'],
        ]);
        $status = match ($data['decision']) {
            'screening' => ManuscriptStatus::InitialScreening,
            'send_to_review' => ManuscriptStatus::UnderReview,
            'revision' => ManuscriptStatus::RevisionRequested,
            'accept' => ManuscriptStatus::Accepted,
            'reject' => ManuscriptStatus::Rejected,
        };
        $current = ManuscriptStatus::tryFrom($manuscript->status);
        abort_unless($current && in_array($status, $current->next(), true), 422, 'This decision is not valid from the current manuscript status.');

        if ($data['decision'] === 'accept') {
            $assignments = ReviewAssignment::query()->whereHas('round', fn ($query) => $query->where('manuscript_id', $manuscript->id))->get();
            $hasCompletedReview = $assignments->contains(fn (ReviewAssignment $assignment) => $assignment->status === 'completed');
            $hasPendingReview = $assignments->contains(fn (ReviewAssignment $assignment) => in_array($assignment->status, ['invited', 'accepted', 'in_progress'], true));
            abort_unless($hasCompletedReview && !$hasPendingReview, 422, 'Acceptance requires at least one completed review and no outstanding reviewer invitations.');
        }

        DB::transaction(function () use ($request, $manuscript, $workflow, $status, $data): void {
            $workflow->transition($manuscript, $status, $request->user(), $data['note'] ?? null);
            if (in_array($data['decision'], ['revision', 'accept', 'reject'], true)) {
                EditorialDecision::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'editor_id' => $request->user()->id,
                    'decision' => $data['decision'],
                    'author_visible_reason' => $data['note'] ?? null,
                    'decision_letter' => $data['decision_letter'] ?? null,
                    'decided_at' => now(),
                ]);
                $roundIds = ReviewRound::query()->where('manuscript_id', $manuscript->id)->pluck('id');
                ReviewAssignment::query()->whereIn('review_round_id', $roundIds)
                    ->whereIn('status', ['invited', 'accepted', 'in_progress'])
                    ->update(['status' => 'cancelled', 'completed_at' => now(), 'updated_at' => now()]);
                ReviewRound::query()->whereIn('id', $roundIds)->update(['status' => 'closed', 'closed_at' => now(), 'updated_at' => now()]);
            }
        });

        $message = 'Editorial status updated.';
        $corresponding = $manuscript->authors()->where('is_corresponding', true)->first();
        if ($corresponding && in_array($data['decision'], ['revision', 'accept', 'reject'], true)) {
            $subject = 'Editorial update: '.$manuscript->reference;
            $letter = trim(($data['note'] ?? '')."\n\n".($data['decision_letter'] ?? ''));
            $correspondence = Correspondence::query()->create([
                'manuscript_id' => $manuscript->id,
                'sender_id' => $request->user()->id,
                'recipient_id' => $corresponding->user_id,
                'recipient_email' => $corresponding->email,
                'audience' => 'author',
                'subject' => $subject,
                'body' => $letter,
            ]);
            try {
                Mail::to($corresponding->email)->send(new ManuscriptDecisionMail($manuscript->fresh('journal'), $data['decision'], $data['decision_letter'] ?? '', $data['note'] ?? ''));
                $correspondence->update(['sent_at' => now()]);
                $message .= ' The author notice was handed to the configured mail transport.';
            } catch (Throwable $exception) {
                report($exception);
                $message .= ' The decision is saved, but the email could not be handed to the configured mail transport.';
            }
        } elseif (in_array($data['decision'], ['revision', 'accept', 'reject'], true)) {
            $message .= ' No corresponding-author email was available, so no email was sent.';
        }

        return back()->with('status', $message);
    }

    public function assignEditor(Request $request, Manuscript $manuscript): RedirectResponse
    {
        $data = $request->validate(['editor_id' => ['required', 'integer', 'exists:users,id']]);
        $editor = User::query()->where('account_status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['editor', 'administrator']))->findOrFail($data['editor_id']);
        if (!$request->user()->hasRole('administrator')) {
            abort_unless($editor->id === $request->user()->id, 403, 'Editors can only assign the manuscript to themselves.');
        }

        DB::transaction(function () use ($request, $manuscript, $editor): void {
            $active = EditorAssignment::query()->where('manuscript_id', $manuscript->id)->whereNull('ended_at')->get();
            if ($active->contains(fn (EditorAssignment $assignment) => $assignment->editor_id === $editor->id)) {
                return;
            }
            EditorAssignment::query()->where('manuscript_id', $manuscript->id)->whereNull('ended_at')->update(['ended_at' => now(), 'updated_at' => now()]);
            $assignment = EditorAssignment::query()->create([
                'manuscript_id' => $manuscript->id,
                'editor_id' => $editor->id,
                'assigned_by' => $request->user()->id,
                'assignment_role' => 'handling-editor',
                'assigned_at' => now(),
            ]);
            AuditLog::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'editor.assigned',
                'subject_type' => EditorAssignment::class,
                'subject_id' => $assignment->id,
                'metadata' => ['manuscript_id' => $manuscript->id, 'editor_id' => $editor->id],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Handling-editor assignment updated.');
    }

    public function assignReviewer(Request $request, Manuscript $manuscript): RedirectResponse
    {
        abort_unless($manuscript->status === 'under_review', 422, 'Move the manuscript to peer review before inviting a reviewer.');
        $data = $request->validate(['reviewer_id' => ['required', 'integer', 'exists:users,id'], 'due_at' => ['nullable', 'date', 'after:today']]);
        $reviewer = User::query()->where('account_status', 'active')->whereNotNull('email_verified_at')->findOrFail($data['reviewer_id']);
        abort_unless($reviewer->hasRole('reviewer'), 422, 'Select a verified account with the reviewer role.');

        $assignment = DB::transaction(function () use ($request, $manuscript, $reviewer, $data): ReviewAssignment {
            $round = ReviewRound::query()->where('manuscript_id', $manuscript->id)->where('status', 'open')->orderByDesc('round_number')->first();
            if (!$round) {
                $round = ReviewRound::query()->create([
                    'manuscript_id' => $manuscript->id,
                    'round_number' => (int) $manuscript->reviewRounds()->max('round_number') + 1,
                    'status' => 'open',
                    'opened_by' => $request->user()->id,
                    'opened_at' => now(),
                ]);
            }
            $assignment = ReviewAssignment::query()->firstOrCreate(
                ['review_round_id' => $round->id, 'reviewer_email' => $reviewer->email],
                ['reviewer_id' => $reviewer->id, 'status' => 'invited', 'invited_at' => now(), 'due_at' => $data['due_at'] ?? null],
            );
            if ($assignment->wasRecentlyCreated) {
                AuditLog::query()->create([
                    'actor_id' => $request->user()->id,
                    'action' => 'reviewer.assigned',
                    'subject_type' => ReviewAssignment::class,
                    'subject_id' => $assignment->id,
                    'metadata' => ['manuscript_id' => $manuscript->id, 'reviewer_id' => $reviewer->id],
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500),
                    'created_at' => now(),
                ]);
            }

            return $assignment;
        });
        if (!$assignment->wasRecentlyCreated) {
            return back()->with('status', 'This reviewer already has an assignment in the current review round.');
        }

        $subject = 'Aksujet peer-review invitation: '.$manuscript->reference;
        $correspondence = Correspondence::query()->create([
            'manuscript_id' => $manuscript->id,
            'sender_id' => $request->user()->id,
            'recipient_id' => $reviewer->id,
            'recipient_email' => $reviewer->email,
            'audience' => 'reviewer',
            'subject' => $subject,
            'body' => 'A peer-review invitation was created for reference '.$manuscript->reference.'. Manuscript details are available only after you sign in and accept the assignment.',
        ]);
        try {
            Mail::to($reviewer->email)->send(new ReviewInvitationMail($manuscript, $assignment));
            $correspondence->update(['sent_at' => now()]);
            $message = 'Reviewer assignment saved and invitation handed to the configured mail transport.';
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Reviewer assignment saved, but the invitation email could not be handed to the configured mail transport.';
        }

        return back()->with('status', $message);
    }

    public function storePublication(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'manuscript_id' => ['required', 'integer', 'exists:manuscripts,id'],
            'issue_id' => ['required', 'integer', 'exists:issues,id'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:articles,slug'],
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'min:50', 'max:20000'],
            'keywords' => ['nullable', 'string', 'max:2000'],
            'doi' => ['nullable', 'string', 'max:191', 'regex:/^10\.\d{4,9}\/\S+$/u', 'unique:articles,doi'],
            'page_range' => ['nullable', 'string', 'max:40'],
            'article_number' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'license' => ['nullable', 'string', 'max:120'],
            'final_pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);
        $manuscript = Manuscript::query()->with(['journal', 'authors', 'article'])->findOrFail($data['manuscript_id']);
        abort_unless($manuscript->status === 'accepted', 422, 'Only an accepted manuscript can enter the publication archive.');
        abort_unless(!$manuscript->article, 422, 'A publication record already exists for this manuscript.');
        $issue = Issue::query()->where('journal_id', $manuscript->journal_id)->findOrFail($data['issue_id']);
        $file = $request->file('final_pdf');
        $slug = ($data['slug'] ?? null) ?: Str::slug($manuscript->journal->slug.'-volume-'.$issue->volume.'-issue-'.$issue->issue_number.'-'.$data['title']);
        if ($slug === '') {
            $slug = 'article-'.Str::lower(Str::random(12));
        }
        $path = $file->storeAs('publication-files/'.now()->format('Y/m'), Str::uuid().'.pdf', 'manuscripts');
        $keywords = array_slice(array_values(array_unique(array_filter(array_map('trim', explode(',', $data['keywords'] ?? ''))))), 0, 30);

        try {
            $article = DB::transaction(function () use ($request, $manuscript, $issue, $file, $data, $slug, $path, $keywords): Article {
                $article = Article::query()->create([
                    'journal_id' => $manuscript->journal_id,
                    'issue_id' => $issue->id,
                    'manuscript_id' => $manuscript->id,
                    'slug' => $slug,
                    'title' => $data['title'],
                    'abstract' => $data['abstract'],
                    'keywords' => $keywords,
                    'doi' => $data['doi'] ?? null,
                    'license' => $data['license'] ?? null,
                    'page_range' => $data['page_range'] ?? null,
                    'article_number' => $data['article_number'] ?? null,
                    'status' => 'draft',
                    'published_at' => null,
                ]);
                foreach ($manuscript->authors as $author) {
                    ArticleAuthor::query()->create([
                        'article_id' => $article->id,
                        'position' => $author->position,
                        'given_name' => $author->given_name,
                        'family_name' => $author->family_name,
                        'affiliation' => $author->affiliation,
                        'orcid' => $author->orcid,
                        'email' => $author->is_corresponding ? $author->email : null,
                        'is_corresponding' => $author->is_corresponding,
                    ]);
                }
                $articleFile = ArticleFile::query()->create([
                    'article_id' => $article->id,
                    'kind' => 'full-text',
                    'disk' => 'manuscripts',
                    'path' => $path,
                    'original_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 180, ''),
                    'mime_type' => 'application/pdf',
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'is_public' => false,
                ]);
                ArticleVersion::query()->create([
                    'article_id' => $article->id,
                    'article_file_id' => $articleFile->id,
                    'replaced_by' => $request->user()->id,
                    'version_number' => 1,
                    'change_note' => 'First accepted publication file.',
                    'published_at' => null,
                ]);
                AuditLog::query()->create([
                    'actor_id' => $request->user()->id,
                    'action' => 'article.drafted',
                    'subject_type' => Article::class,
                    'subject_id' => $article->id,
                    'metadata' => ['manuscript_id' => $manuscript->id, 'issue_id' => $issue->id],
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500),
                    'created_at' => now(),
                ]);

                return $article;
            });
        } catch (Throwable $exception) {
            Storage::disk('manuscripts')->delete($path);
            throw $exception;
        }

        return redirect()->route('editor.manuscripts.show', $manuscript)->with('status', 'Publication draft saved privately. Publish its issue, then release this article when it is ready.');
    }

    public function publishArticle(Request $request, Article $article): RedirectResponse
    {
        $article->load(['issue', 'journal', 'files', 'manuscript']);
        abort_unless($article->status === 'draft' && ($article->manuscript_id === null || $article->manuscript?->status === 'accepted'), 422, 'Only an imported archive draft or a draft from an accepted manuscript can be released.');
        abort_unless($article->issue?->status === 'published' && $article->journal?->status === 'active', 422, 'Publish the active journal issue before releasing this article.');
        $file = $article->files()->where('kind', 'full-text')->latest('id')->firstOrFail();
        abort_unless($file->disk === 'manuscripts' && Storage::disk($file->disk)->exists($file->path), 404);

        DB::transaction(function () use ($request, $article, $file): void {
            $locked = Article::query()->lockForUpdate()->findOrFail($article->id);
            abort_unless($locked->status === 'draft', 422, 'This article is no longer a draft.');
            abort_unless($locked->issue?->status === 'published' && $locked->journal?->status === 'active', 422, 'The issue or journal is not public.');
            $locked->update(['status' => 'published', 'published_at' => now()]);
            $file->update(['is_public' => true]);
            ArticleVersion::query()->where('article_id', $locked->id)->where('article_file_id', $file->id)->update(['published_at' => now()]);
            AuditLog::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'article.published',
                'subject_type' => Article::class,
                'subject_id' => $locked->id,
                'metadata' => ['issue_id' => $locked->issue_id, 'journal_id' => $locked->journal_id],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'created_at' => now(),
            ]);
        });

        $destination = $article->manuscript_id ? redirect()->route('editor.manuscripts.show', $article->manuscript) : redirect()->route('editor.archive.index');

        return $destination->with('status', 'Article and final PDF are now public.');
    }

    public function storeJournal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_title' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:journals,slug'],
            'issn' => ['nullable', 'string', 'regex:/^\d{4}-\d{3}[\dX]$/', 'unique:journals,issn'],
            'eissn' => ['nullable', 'string', 'regex:/^\d{4}-\d{3}[\dX]$/', 'unique:journals,eissn'],
            'description' => ['nullable', 'string', 'max:5000'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);
        $slug = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        if ($slug === '') {
            $slug = 'journal-'.Str::lower(Str::random(12));
        }
        if (Journal::query()->where('slug', $slug)->exists()) {
            if ($data['slug'] ?? null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'This journal URL is already in use. Choose another slug.']);
            }
            $slug .= '-'.Str::lower(Str::random(6));
        }

        $journal = Journal::query()->create([
            'title' => $data['title'],
            'short_title' => $data['short_title'] ?? null,
            'slug' => $slug,
            'issn' => $data['issn'] ?? null,
            'eissn' => $data['eissn'] ?? null,
            'description' => $data['description'] ?? null,
            'scope' => $data['scope'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'status' => 'active',
        ]);
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'journal.created',
            'subject_type' => Journal::class,
            'subject_id' => $journal->id,
            'metadata' => ['slug' => $journal->slug],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'created_at' => now(),
        ]);

        return redirect()->route('editor.dashboard')->with('status', 'Journal created. You can now create an issue for it.');
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_id' => ['required', 'integer', Rule::exists('journals', 'id')->where('status', 'active')],
            'volume' => ['required', 'integer', 'min:1', 'max:65535'],
            'issue_number' => [
                'required', 'integer', 'min:1', 'max:65535',
                Rule::unique('issues', 'issue_number')->where(fn ($query) => $query
                    ->where('journal_id', $request->input('journal_id'))
                    ->where('volume', $request->input('volume'))
                    ->where('publication_year', $request->input('publication_year'))),
            ],
            'publication_year' => ['required', 'integer', 'min:1900', 'max:2200'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $journal = Journal::query()->where('status', 'active')->findOrFail($data['journal_id']);
        $slug = Str::slug($journal->slug.'-volume-'.$data['volume'].'-issue-'.$data['issue_number'].'-'.$data['publication_year']);
        $issue = Issue::query()->create([
            'journal_id' => $journal->id,
            'volume' => $data['volume'],
            'issue_number' => $data['issue_number'],
            'publication_year' => $data['publication_year'],
            'title' => ($data['title'] ?? null) ?: "Volume {$data['volume']}, Issue {$data['issue_number']} ({$data['publication_year']})",
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => 'draft',
        ]);
        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'issue.created',
            'subject_type' => Issue::class,
            'subject_id' => $issue->id,
            'metadata' => ['journal_id' => $journal->id],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Issue created as a private draft.');
    }

    public function publishIssue(Request $request, Issue $issue): RedirectResponse
    {
        abort_unless($issue->status === 'draft' && $issue->journal?->status === 'active', 422, 'Only a draft issue for an active journal can be published.');
        DB::transaction(function () use ($request, $issue): void {
            $issue->update(['status' => 'published', 'published_on' => $issue->published_on ?? now()->toDateString()]);
            AuditLog::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'issue.published',
                'subject_type' => Issue::class,
                'subject_id' => $issue->id,
                'metadata' => ['journal_id' => $issue->journal_id],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Issue is now public. Accepted article records can be released into it.');
    }
}
