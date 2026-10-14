<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    protected $signature = 'amlux:user {email : Login email} {--name= : Full name} {--password= : Password (generated when omitted)}';

    protected $description = 'Create a back-office user, or reset the password of an existing one';

    public function handle()
    {
        $email = strtolower($this->argument('email'));
        $password = $this->option('password') ?: Str::random(12);

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($password)] + ($this->option('name') ? ['name' => $this->option('name')] : []));
            $this->info("Password updated for {$email}.");
        } else {
            User::create([
                'name' => $this->option('name') ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->info("User {$email} created.");
        }

        if (! $this->option('password')) {
            $this->line("Generated password: {$password}");
        }

        return 0;
    }
}
