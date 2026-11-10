<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('affiliation')->nullable()->after('email');
            $table->string('orcid', 19)->nullable()->unique()->after('affiliation');
            $table->string('account_status', 24)->default('active')->index()->after('orcid');
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 40)->unique();
            $table->string('label', 80);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['orcid']);
            $table->dropIndex(['account_status']);
            $table->dropColumn(['affiliation', 'orcid', 'account_status']);
        });
    }
};
