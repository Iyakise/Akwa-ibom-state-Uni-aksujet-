<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug', 191)->unique();
            $table->string('short_title')->nullable();
            $table->string('issn', 16)->nullable()->unique();
            $table->string('eissn', 16)->nullable()->unique();
            $table->text('description')->nullable();
            $table->text('scope')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('volume');
            $table->unsignedSmallInteger('issue_number');
            $table->unsignedSmallInteger('publication_year');
            $table->string('title')->nullable();
            $table->string('slug', 191);
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->date('published_on')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['journal_id', 'volume', 'issue_number', 'publication_year'], 'issues_journal_volume_no_year_unique');
            $table->unique(['journal_id', 'slug'], 'issues_journal_slug_unique');
            $table->index(['journal_id', 'status', 'published_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
        Schema::dropIfExists('journals');
    }
};
