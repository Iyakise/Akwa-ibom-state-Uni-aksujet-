<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manuscript_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email')->nullable();
            $table->string('audience', 24)->default('author');
            $table->string('subject');
            $table->longText('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['manuscript_id', 'audience', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });

        Schema::create('correspondence_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correspondence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk', 40)->default('manuscripts');
            $table->string('path', 1024);
            $table->string('original_name');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_attachments');
        Schema::dropIfExists('correspondence');
    }
};
