<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorialArchiveImportTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER_EMAIL = 'justiceuyompcs@gmail.com';

    protected function setUp(): void
    {
        parent::setUp();
        config(['aksujet.editorial_allowlist' => [self::OWNER_EMAIL]]);
        $this->seed();
    }

    public function test_editorial_studio_denies_editor_accounts_outside_the_server_allowlist(): void
    {
        $editor = User::factory()->create(['email' => 'editor@example.com']);
        $editor->assignRole('editor');

        $this->actingAs($editor)
            ->get(route('editor.dashboard'))
            ->assertForbidden();
    }

    public function test_allowlisted_owner_can_open_the_editorial_studio(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)
            ->get(route('editor.dashboard'))
            ->assertOk();
    }

    public function test_owner_can_create_a_journal_and_issue_with_generated_slug_and_title(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->post(route('editor.journals.store'), [
            'title' => 'AKSU Test Journal of Applied Science',
            'short_title' => 'AKSU-TJAS',
            'description' => 'A test journal record.',
        ])->assertRedirect(route('editor.dashboard'));

        $journal = Journal::query()->where('slug', 'aksu-test-journal-of-applied-science')->firstOrFail();
        $this->actingAs($owner)->post(route('editor.issues.store'), [
            'journal_id' => $journal->id,
            'volume' => 1,
            'issue_number' => 1,
            'publication_year' => 2027,
        ])->assertRedirect();

        $issue = Issue::query()->where('journal_id', $journal->id)->firstOrFail();
        $this->assertSame('Volume 1, Issue 1 (2027)', $issue->title);
        $this->assertSame('aksu-test-journal-of-applied-science-volume-1-issue-1-2027', $issue->slug);
        $this->assertSame('draft', $issue->status);
    }

    public function test_nine_supplied_pdfs_are_available_in_the_private_editor_import_desk(): void
    {
        $owner = $this->createOwner();
        $stagedPdfs = Storage::disk('manuscripts')->files('imported-articles');

        $this->assertCount(9, $stagedPdfs);
        $this->actingAs($owner)->get(route('editor.archive.index'))
            ->assertOk()
            ->assertSee('comprehensive-analysis-multi-stage-cyber-attack-detection-prevention-machine-learning-review.pdf')
            ->assertSee('steady-state-thermal-modelling-validation-squirrel-cage-induction-motor.pdf');
    }

    public function test_allowlisted_registration_receives_administrator_role(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Aksujet Owner',
            'email' => self::OWNER_EMAIL,
            'password' => 'AksujetSecure!2026',
            'password_confirmation' => 'AksujetSecure!2026',
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertAuthenticated();
        $owner = User::query()->where('email', self::OWNER_EMAIL)->firstOrFail();
        $this->assertTrue($owner->hasRole('administrator'));
        $this->assertFalse($owner->hasVerifiedEmail());
    }

    public function test_imported_pdf_stays_private_until_an_editor_releases_it(): void
    {
        Storage::fake('manuscripts');
        Storage::disk('manuscripts')->put('imported-articles/research-paper.pdf', "%PDF-1.7\nprivate test PDF content");
        $owner = $this->createOwner();
        $issue = Issue::query()->firstOrFail();
        $issue->update(['status' => 'published', 'published_on' => '2020-06-15']);

        $response = $this->actingAs($owner)->post(route('editor.archive.store'), [
            'source_path' => 'imported-articles/research-paper.pdf',
            'issue_id' => $issue->id,
            'title' => 'An Example of Editorially Verified Research',
            'abstract' => 'This abstract contains a sufficiently descriptive summary of the work, the method, the evidence, and the principal findings for readers and search engines.',
            'authors' => 'Amina | Okon | Akwa Ibom State University |',
            'keywords' => 'research, archive, publishing',
            'publication_date' => '2020-06-15',
            'page_range' => '1–10',
        ]);

        $response->assertRedirect(route('editor.archive.index'));
        $article = Article::query()->with('files')->firstOrFail();
        $this->assertSame('draft', $article->status);
        $this->assertNull($article->published_at);
        $this->assertFalse($article->files->firstOrFail()->is_public);
        $this->assertSame('2020-06-15', $article->publication_date->toDateString());
        $this->get(route('articles.show', $article))->assertNotFound();
        $this->get(route('articles.pdf', $article))->assertNotFound();

        $this->actingAs($owner)->post(route('editor.articles.publish', $article))
            ->assertRedirect(route('editor.archive.index'));

        $article->refresh();
        $file = ArticleFile::query()->where('article_id', $article->id)->firstOrFail();
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);
        $this->assertTrue($file->is_public);
        $this->get(route('articles.show', $article))->assertOk()->assertSee('An Example of Editorially Verified Research');
        $this->get(route('articles.pdf', $article))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    private function createOwner(): User
    {
        $owner = User::factory()->create([
            'email' => self::OWNER_EMAIL,
            'email_verified_at' => now(),
            'account_status' => 'active',
        ]);
        $owner->assignRole(Role::query()->where('name', 'administrator')->firstOrFail()->name);

        return $owner;
    }
}
