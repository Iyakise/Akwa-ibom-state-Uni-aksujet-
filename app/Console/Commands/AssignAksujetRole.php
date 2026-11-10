<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignAksujetRole extends Command
{
    protected $signature = 'aksujet:assign-role {email : Registered, verified account email} {role : administrator, editor, reviewer, or author}';
    protected $description = 'Assign an Aksujet role from the trusted server command line.';

    public function handle(): int
    {
        $role = (string) $this->argument('role');
        if (!in_array($role, ['administrator', 'editor', 'reviewer', 'author'], true)) {
            $this->error('Choose administrator, editor, reviewer, or author.');
            return self::INVALID;
        }

        $user = User::query()->where('email', Str::lower((string) $this->argument('email')))->first();
        if (!$user) {
            $this->error('No matching registered account. Ask the person to register first.');
            return self::FAILURE;
        }
        if (!$user->email_verified_at) {
            $this->error('The account must verify its email before receiving an elevated editorial role.');
            return self::FAILURE;
        }
        if ($user->account_status !== 'active') {
            $this->error('The account is not active.');
            return self::FAILURE;
        }

        $user->assignRole($role);
        AuditLog::query()->create([
            'actor_id' => null,
            'action' => 'user.role_assigned_by_cli',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => ['role' => $role],
            'created_at' => now(),
        ]);

        $this->info("Assigned the {$role} role to {$user->email}.");
        return self::SUCCESS;
    }
}
