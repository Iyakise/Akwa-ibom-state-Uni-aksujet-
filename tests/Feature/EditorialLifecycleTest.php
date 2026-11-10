<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\ReviewAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER_EMAIL = 'justiceuyompcs@gmail.com';

    protected function setUp(): void
    {
        parent::setUp();
        config(['aksujet.editorial_allowlist' => [self::OWNER_EMAIL]]);
        $this->seed();
        Storage::fake('manuscripts');
        Mail::fake();
    }

    public function test_manuscript_moves_from_submission_through_review_to_publication(): void
    {
        $journal = Journal::query()->where('status', 'active')->firstOrFail();
        $issue = Issue::query()->where('journal_id', $journal->id)->firstOrFail();
        $author = $this->userWithRole('author', 'author@example.com', 'Dr Amina Author');
        $reviewer = $this->userWithRole('reviewer', 'reviewer@example.com', 'Dr Reviewer');
        $editor = $this->userWithRole('administrator', self::OWNER_EMAIL, 'Aksujet Owner');

        $this->actingAs($author)->post(route('author.submissions.store'), [
            'journal_id' => $journal->id,
            'article_type' => 'research-article',
            'title' => 'A Testable Manuscript for the Full Editorial Lifecycle',
            'abstract' => 'This sufficiently detailed abstract summarizes the study question, the research method, the data, and the findings for editorial and peer-review assessment.',
            'author_name' => 'Amina Author',
            'affiliation' => 'Akwa Ibom State University',
            'coauthors' => '',
            'manuscript_file' => UploadedFile::fake()->create('manuscript.pdf', 120, 'application/pdf'),
            'confirm_originality' => '1',
            'confirm_ethics' => '1',
        ])->assertRedirect();

        $manuscript = Manuscript::query()->with('files')->firstOrFail();
        $this->assertSame('submitted', $manuscript->status);
        $this->assertDatabaseHas('manuscript_status_histories', ['manuscript_id' => $manuscript->id, 'to_status' => 'submitted']);
        $this->get(route('author.submissions.show', $manuscript))->assertOk();
        $otherAuthor = $this->userWithRole('author', 'other-author@example.com', 'Another Author');
        $this->actingAs($otherAuthor)->get(route('author.submissions.show', $manuscript))->assertNotFound();

        $this->actingAs($editor)->patch(route('editor.manuscripts.transition', $manuscript), [
            'decision' => 'screening',
            'note' => 'Initial administrative screening started.',
        ])->assertRedirect();
        $manuscript->refresh();
        $this->assertSame('initial_screening', $manuscript->status);

        $this->actingAs($editor)->patch(route('editor.manuscripts.transition', $manuscript), [
            'decision' => 'send_to_review',
            'note' => 'The submission passed screening and is ready for peer review.',
        ])->assertRedirect();
        $manuscript->refresh();
        $this->assertSame('under_review', $manuscript->status);

        $this->actingAs($editor)->post(route('editor.manuscripts.reviewers.store', $manuscript), [
            'reviewer_id' => $reviewer->id,
            'due_at' => now()->addDays(14)->toDateString(),
        ])->assertRedirect();
        $assignment = ReviewAssignment::query()->firstOrFail();
        $this->assertSame('invited', $assignment->status);

        $this->actingAs($reviewer)->get(route('reviewer.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('A Testable Manuscript for the Full Editorial Lifecycle')
            ->assertDontSee('author@example.com');
        $submissionFile = ManuscriptFile::query()->where('manuscript_id', $manuscript->id)->firstOrFail();
        $this->actingAs($reviewer)->get(route('reviewer.assignments.files.show', [$assignment, $submissionFile]))->assertNotFound();

        $this->actingAs($reviewer)->post(route('reviewer.assignments.accept', $assignment))->assertRedirect();
        $assignment->refresh();
        $this->assertSame('accepted', $assignment->status);
        $this->actingAs($reviewer)->get(route('reviewer.assignments.files.show', [$assignment, $submissionFile]))->assertOk();

        $this->actingAs($reviewer)->post(route('reviewer.assignments.review.store', $assignment), [
            'recommendation' => 'minor_revision',
            'comments_to_author' => 'Please clarify the sampling method and include the missing comparison in the results section.',
            'confidential_comments_to_editor' => 'The work is suitable after the authors address the specific methodological concerns.',
            'ratings' => ['methodology' => 4, 'clarity' => 3],
        ])->assertRedirect();
        $assignment->refresh();
        $this->assertSame('completed', $assignment->status);
        $this->actingAs($editor)->get(route('editor.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Please clarify the sampling method')
            ->assertSee('Confidential to the editor');

        $this->actingAs($editor)->patch(route('editor.manuscripts.transition', $manuscript), [
            'decision' => 'accept',
            'note' => 'The required revisions have been addressed.',
            'decision_letter' => 'We are pleased to accept this manuscript for publication.',
        ])->assertRedirect();
        $manuscript->refresh();
        $this->assertSame('accepted', $manuscript->status);
        $this->assertDatabaseHas('editorial_decisions', ['manuscript_id' => $manuscript->id, 'decision' => 'accept']);
        $this->actingAs($author)->get(route('author.submissions.show', $manuscript))->assertOk()->assertSee('We are pleased to accept this manuscript for publication.');

        $this->actingAs($editor)->post(route('editor.publications.store'), [
            'manuscript_id' => $manuscript->id,
            'issue_id' => $issue->id,
            'title' => $manuscript->title,
            'abstract' => $manuscript->abstract,
            'keywords' => 'editorial workflow, peer review',
            'page_range' => '1–12',
            'final_pdf' => UploadedFile::fake()->create('final-article.pdf', 150, 'application/pdf'),
        ])->assertRedirect();

        $article = Article::query()->with('files')->where('manuscript_id', $manuscript->id)->firstOrFail();
        $this->assertSame('draft', $article->status);
        $this->assertFalse($article->files->firstOrFail()->is_public);
        $this->get(route('articles.show', $article))->assertNotFound();
        $this->get(route('articles.pdf', $article))->assertNotFound();

        $this->actingAs($editor)->post(route('editor.issues.publish', $issue))->assertRedirect();
        $this->actingAs($editor)->post(route('editor.articles.publish', $article))->assertRedirect();
        $article->refresh();
        $publicFile = ArticleFile::query()->where('article_id', $article->id)->firstOrFail();
        $this->assertSame('published', $article->status);
        $this->assertTrue($publicFile->is_public);
        $this->get(route('articles.show', $article))->assertOk()->assertSee($article->title);
        $this->get(route('articles.pdf', $article))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('sitemap'))->assertOk()->assertSee($article->slug);
    }

    private function userWithRole(string $role, string $email, string $name): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
            'email_verified_at' => now(),
            'account_status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
