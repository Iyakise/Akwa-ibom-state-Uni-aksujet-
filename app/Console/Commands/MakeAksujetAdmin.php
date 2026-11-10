<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MakeAksujetAdmin extends Command
{
    protected $signature = 'aksujet:make-admin {email : Email of a registered and verified account}';
    protected $description = 'Grant administrator access to a verified Aksujet account from the server CLI.';

    public function handle(): int
    {
        return Artisan::call('aksujet:assign-role', [
            'email' => $this->argument('email'),
            'role' => 'administrator',
        ], $this->output);
    }
}
