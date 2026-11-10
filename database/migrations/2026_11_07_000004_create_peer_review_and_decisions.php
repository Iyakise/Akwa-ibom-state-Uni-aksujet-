<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->string('status', 24)->default('open')->index();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['manuscript_id', 'round_number']);
        });

        Schema::create('review_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_email', 191);
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->string('status', 24)->default('invited')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['review_round_id', 'reviewer_email']);
            $table->index(['review_round_id', 'status']);
            $table->index(['reviewer_id', 'status']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('recommendation', 32)->nullable();
            $table->longText('comments_to_author')->nullable();
            $table->longText('confidential_comments_to_editor')->nullable();
            $table->json('ratings')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('editorial_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_round_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 32);
            $table->text('author_visible_reason')->nullable();
            $table->longText('decision_letter')->nullable();
            $table->timestamp('decided_at')->useCurrent();
            $table->timestamps();
            $table->index(['manuscript_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_decisions');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('review_assignments');
        Schema::dropIfExists('review_rounds');
    }
};
