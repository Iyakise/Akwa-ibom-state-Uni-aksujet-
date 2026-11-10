<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        $journal = Journal::query()->where('status', 'active')->orderBy('id')->first();
        $issues = Issue::query()->where('status', 'published')
            ->whereHas('journal', fn ($query) => $query->where('status', 'active'))
            ->with('journal')->orderByDesc('published_on')->orderByDesc('publication_year')->limit(6)->get();
        $articles = Article::query()->published()->with(['journal', 'issue', 'authors'])
            ->orderByDesc('published_at')->paginate(9);

        return view('public.home', compact('journal', 'issues', 'articles'));
    }

    public function journal(Journal $journal): View
    {
        abort_unless($journal->status === 'active', 404);
        $issues = Issue::query()->where('journal_id', $journal->id)->where('status', 'published')
            ->orderByDesc('published_on')->orderByDesc('publication_year')->get();
        $articles = $journal->articles()->published()->with(['journal', 'issue', 'authors'])
            ->orderByDesc('published_at')->paginate(12);

        return view('public.journal', compact('journal', 'issues', 'articles'));
    }

    public function issue(Issue $issue): View
    {
        abort_unless($issue->status === 'published' && $issue->journal?->status === 'active', 404);
        $articles = $issue->articles()->published()->with(['journal', 'issue', 'authors'])
            ->orderBy('article_number')->orderBy('title')->paginate(24);

        return view('public.issue', compact('issue', 'articles'));
    }

    public function article(Article $article): View
    {
        $article = Article::query()->published()->whereKey($article->id)
            ->with(['journal', 'issue', 'authors', 'publicFiles'])
            ->firstOrFail();

        return view('public.article', compact('article'));
    }

    public function pdf(Article $article): Response
    {
        $article = Article::query()->published()->whereKey($article->id)->with('journal')->firstOrFail();
        $file = $article->publicFiles()->where('kind', 'full-text')->latest('id')->firstOrFail();
        abort_unless($file->disk === 'manuscripts', 404);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);
        $stream = $disk->readStream($file->path);
        abort_unless(is_resource($stream), 404);
        $safeName = str_replace(["\r", "\n", '"'], '', basename(str_replace('\\', '/', $file->original_name)));
        $encodedName = rawurlencode($safeName ?: 'aksujet-article.pdf');
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"article.pdf\"; filename*=UTF-8''{$encodedName}",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'Content-Length' => (string) $disk->size($file->path),
        ];
        if ($file->sha256) {
            $headers['ETag'] = '"'.$file->sha256.'"';
        }

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, 200, $headers);
    }

    public function robots(): Response
    {
        $content = "User-agent: *\nAllow: /\nDisallow: /login\nDisallow: /register\nDisallow: /password/\nDisallow: /email/\nDisallow: /dashboard\nDisallow: /profile\nDisallow: /author/\nDisallow: /editor/\nDisallow: /reviewer/\nSitemap: ".route('sitemap')."\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $journals = Journal::query()->where('status', 'active')->orderBy('slug')->get();
        $issues = Issue::query()->where('status', 'published')
            ->whereHas('journal', fn ($query) => $query->where('status', 'active'))
            ->orderByDesc('published_on')->get();
        $articles = Article::query()->published()->orderByDesc('published_at')->get();

        return response()->view('seo.sitemap', compact('journals', 'issues', 'articles'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
