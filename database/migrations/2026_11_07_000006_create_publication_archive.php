<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manuscript_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('slug', 191)->unique();
            $table->string('title');
            $table->longText('abstract');
            $table->json('keywords')->nullable();
            $table->string('doi', 191)->nullable()->unique();
            $table->string('license', 120)->nullable();
            $table->string('language', 12)->default('en');
            $table->string('page_range', 40)->nullable();
            $table->unsignedSmallInteger('article_number')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['journal_id', 'status', 'published_at']);
            $table->index(['issue_id', 'status', 'article_number']);
        });

        Schema::create('article_authors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('given_name');
            $table->string('family_name');
            $table->string('affiliation')->nullable();
            $table->string('orcid', 19)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_corresponding')->default(false);
            $table->timestamps();
            $table->unique(['article_id', 'position']);
        });

        Schema::create('article_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32)->default('full-text');
            $table->string('disk', 40)->default('manuscripts');
            $table->string('path', 1024);
            $table->string('original_name');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->index(['article_id', 'kind', 'is_public']);
        });

        Schema::create('article_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_file_id')->constrained()->restrictOnDelete();
            $table->foreignId('replaced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->text('change_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_versions');
        Schema::dropIfExists('article_files');
        Schema::dropIfExists('article_authors');
        Schema::dropIfExists('articles');
    }
};
