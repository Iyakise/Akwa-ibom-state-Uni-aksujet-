<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'administrator', 'label' => 'Administrator', 'description' => 'Full system configuration and access management.'],
            ['name' => 'editor', 'label' => 'Editor', 'description' => 'Editorial screening, assignment, decisions and publication.'],
            ['name' => 'reviewer', 'label' => 'Reviewer', 'description' => 'Access only to assigned review tasks.'],
            ['name' => 'author', 'label' => 'Author', 'description' => 'Manuscript submission and submission tracking.'],
        ] as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }

        $journal = Journal::query()->firstOrCreate(
            ['slug' => 'aksu-journal-of-engineering-and-technology'],
            [
                'title' => 'AKSU Journal of Engineering and Technology',
                'short_title' => 'Aksujet',
                'description' => 'The scholarly home for engineering, technology and applied research at Akwa Ibom State University.',
                'scope' => 'Engineering, technology and applied research.',
                'contact_email' => 'aksujet@aksu.edu.ng',
                'status' => 'active',
            ],
        );

        foreach ([
            ['volume' => 1, 'issue_number' => 1, 'publication_year' => 2026, 'slug' => 'volume-1-issue-1-2026', 'title' => 'Volume 1, Issue 1 (2026)'],
            ['volume' => 1, 'issue_number' => 2, 'publication_year' => 2026, 'slug' => 'volume-1-issue-2-2026', 'title' => 'Volume 1, Issue 2 (2026)'],
        ] as $issue) {
            Issue::query()->firstOrCreate(
                ['journal_id' => $journal->id, 'volume' => $issue['volume'], 'issue_number' => $issue['issue_number'], 'publication_year' => $issue['publication_year']],
                ['slug' => $issue['slug'], 'title' => $issue['title'], 'status' => 'draft'],
            );
        }

        $allowlist = config('aksujet.editorial_allowlist', []);
        if ($allowlist !== []) {
            User::query()->get()->each(function (User $user) use ($allowlist): void {
                if (in_array(strtolower($user->email), $allowlist, true)) {
                    $user->assignRole('administrator');
                }
            });
        }
    }
}
