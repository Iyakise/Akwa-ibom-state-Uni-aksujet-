<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuscripts', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('article_type', 40)->default('research-article');
            $table->string('title');
            $table->longText('abstract');
            $table->text('cover_letter')->nullable();
            $table->json('declarations')->nullable();
            $table->string('status', 32)->default('submitted')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['submitted_by', 'status']);
            $table->index(['journal_id', 'status', 'submitted_at']);
        });

        Schema::create('manuscript_authors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('given_name');
            $table->string('family_name');
            $table->string('email');
            $table->string('affiliation')->nullable();
            $table->string('orcid', 19)->nullable();
            $table->boolean('is_corresponding')->default(false);
            $table->timestamps();
            $table->unique(['manuscript_id', 'position']);
            $table->index(['manuscript_id', 'is_corresponding']);
        });

        Schema::create('manuscript_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->longText('abstract');
            $table->text('cover_letter')->nullable();
            $table->longText('author_response')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['manuscript_id', 'version_number']);
        });

        Schema::create('manuscript_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manuscript_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('kind', 32)->default('manuscript');
            $table->string('disk', 40)->default('manuscripts');
            $table->string('path', 1024);
            $table->string('original_name');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->timestamps();
            $table->index(['manuscript_id', 'kind']);
        });

        Schema::create('manuscript_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['manuscript_id', 'created_at']);
        });

        Schema::create('editor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_role', 32)->default('handling-editor');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['editor_id', 'ended_at']);
            $table->index(['manuscript_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_assignments');
        Schema::dropIfExists('manuscript_status_histories');
        Schema::dropIfExists('manuscript_files');
        Schema::dropIfExists('manuscript_versions');
        Schema::dropIfExists('manuscript_authors');
        Schema::dropIfExists('manuscripts');
    }
};
