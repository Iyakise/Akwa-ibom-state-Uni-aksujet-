<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleVersion;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArchiveImportController extends Controller
{
    public function index(): View
    {
        $usedPaths = ArticleFile::query()->where('disk', 'manuscripts')->where('path', 'like', 'imported-articles/%')->pluck('path')->all();
        $stagedPdfs = collect(Storage::disk('manuscripts')->files('imported-articles'))
            ->filter(fn (string $path) => str_ends_with(Str::lower($path), '.pdf') && !in_array($path, $usedPaths, true))
            ->sort()->map(fn (string $path) => [
                'path' => $path,
                'filename' => basename($path),
                'suggested_title' => Str::of(pathinfo($path, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->squish()->title()->toString(),
            ])->values();
        $issues = Issue::query()->whereHas('journal', fn ($query) => $query->where('status', 'active'))
            ->with('journal')->orderByDesc('publication_year')->orderByDesc('volume')->orderByDesc('issue_number')->get();
        $draftArticles = Article::query()->where('status', 'draft')->with(['journal', 'issue', 'files', 'authors'])
            ->orderByDesc('created_at')->paginate(12);

        return view('editor.archive.import', compact('stagedPdfs', 'issues', 'draftArticles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $stagedPdfs = collect(Storage::disk('manuscripts')->files('imported-articles'))
            ->filter(fn (string $path) => str_ends_with(Str::lower($path), '.pdf'))->values()->all();
        $data = $request->validate([
            'source_path' => ['required', 'string', Rule::in($stagedPdfs)],
            'issue_id' => ['required', 'integer', Rule::exists('issues', 'id')->where(fn ($query) => $query->whereIn('journal_id', Journal::query()->where('status', 'active')->select('id')))],
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'min:50', 'max:20000'],
            'authors' => ['required', 'string', 'min:3', 'max:10000'],
            'keywords' => ['nullable', 'string', 'max:2000'],
            'doi' => ['nullable', 'string', 'max:191', 'regex:/^10\.\d{4,9}\/\S+$/u', 'unique:articles,doi'],
            'publication_date' => ['nullable', 'date', 'before_or_equal:today'],
            'page_range' => ['nullable', 'string', 'max:40'],
            'license' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:articles,slug'],
        ]);

        $issue = Issue::query()->with('journal')->whereHas('journal', fn ($query) => $query->where('status', 'active'))->findOrFail($data['issue_id']);
        $disk = Storage::disk('manuscripts');
        abort_unless($disk->exists($data['source_path']), 404);
        $stream = $disk->readStream($data['source_path']);
        abort_unless(is_resource($stream), 404);
        $signature = fread($stream, 5);
        if ($signature !== '%PDF-') {
            fclose($stream);
            throw ValidationException::withMessages(['source_path' => 'The selected archive file is not a valid PDF.']);
        }
        rewind($stream);
        $hashContext = hash_init('sha256');
        hash_update_stream($hashContext, $stream);
        $sha256 = hash_final($hashContext);
        fclose($stream);
        abort_unless(!ArticleFile::query()->where('disk', 'manuscripts')->where('sha256', $sha256)->exists(), 422, 'This PDF has already been imported into the archive.');

        $authors = $this->parseAuthors($data['authors']);
        $keywords = array_slice(array_values(array_unique(array_filter(array_map('trim', explode(',', $data['keywords'] ?? ''))))), 0, 30);
        $slug = ($data['slug'] ?? null) ?: Str::slug($issue->journal->slug.'-volume-'.$issue->volume.'-issue-'.$issue->issue_number.'-'.$data['title']);
        if ($slug === '') {
            $slug = 'article-'.Str::lower(Str::random(12));
        }
        if (Article::query()->where('slug', $slug)->exists()) {
            if ($data['slug'] ?? null) {
                throw ValidationException::withMessages(['slug' => 'This public article URL is already in use. Choose another slug.']);
            }
            $slug .= '-'.Str::lower(Str::random(6));
        }

        $article = DB::transaction(function () use ($request, $data, $issue, $disk, $sha256, $authors, $keywords, $slug): Article {
            $article = Article::query()->create([
                'journal_id' => $issue->journal_id,
                'issue_id' => $issue->id,
                'manuscript_id' => null,
                'slug' => $slug,
                'title' => $data['title'],
                'abstract' => $data['abstract'],
                'keywords' => $keywords,
                'doi' => $data['doi'] ?? null,
                'license' => $data['license'] ?? null,
                'page_range' => $data['page_range'] ?? null,
                'publication_date' => $data['publication_date'] ?? null,
                'language' => 'en',
                'status' => 'draft',
                'published_at' => null,
            ]);
            foreach ($authors as $position => $author) {
                ArticleAuthor::query()->create([
                    'article_id' => $article->id,
                    'position' => $position + 1,
                    'given_name' => $author['given_name'],
                    'family_name' => $author['family_name'],
                    'affiliation' => $author['affiliation'],
                    'orcid' => $author['orcid'],
                    'email' => null,
                    'is_corresponding' => false,
                ]);
            }
            $file = ArticleFile::query()->create([
                'article_id' => $article->id,
                'kind' => 'full-text',
                'disk' => 'manuscripts',
                'path' => $data['source_path'],
                'original_name' => basename($data['source_path']),
                'mime_type' => 'application/pdf',
                'size_bytes' => $disk->size($data['source_path']),
                'sha256' => $sha256,
                'is_public' => false,
            ]);
            ArticleVersion::query()->create([
                'article_id' => $article->id,
                'article_file_id' => $file->id,
                'replaced_by' => $request->user()->id,
                'version_number' => 1,
                'change_note' => 'Imported from the supplied archive; awaiting editorial verification and publication.',
                'published_at' => null,
            ]);
            AuditLog::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'article.legacy_draft_imported',
                'subject_type' => Article::class,
                'subject_id' => $article->id,
                'metadata' => ['source_filename' => basename($data['source_path']), 'issue_id' => $issue->id, 'sha256' => $sha256],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'created_at' => now(),
            ]);

            return $article;
        });

        return redirect()->route('editor.archive.index')->with('status', 'PDF imported as a private draft. Verify its bibliographic details and publish the issue before releasing the article.');
    }

    /** @return list<array{given_name:string,family_name:string,affiliation:?string,orcid:?string}> */
    private function parseAuthors(string $value): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: [])));
        if ($lines === [] || count($lines) > 50) {
            throw ValidationException::withMessages(['authors' => 'Enter between 1 and 50 authors, one per line.']);
        }
        $authors = [];
        foreach ($lines as $index => $line) {
            $parts = array_map('trim', array_pad(explode('|', $line, 4), 4, ''));
            [$given, $family, $affiliation, $orcid] = $parts;
            if ($given === '' || $family === '') {
                throw ValidationException::withMessages(['authors' => 'Author line '.($index + 1).' needs a given name and family name separated by |.']);
            }
            if ($orcid !== '' && !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
                throw ValidationException::withMessages(['authors' => 'Author line '.($index + 1).' contains an invalid ORCID format.']);
            }
            $authors[] = [
                'given_name' => Str::limit($given, 255, ''),
                'family_name' => Str::limit($family, 255, ''),
                'affiliation' => $affiliation !== '' ? Str::limit($affiliation, 255, '') : null,
                'orcid' => $orcid !== '' ? $orcid : null,
            ];
        }

        return $authors;
    }
}
