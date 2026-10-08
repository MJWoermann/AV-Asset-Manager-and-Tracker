<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AppInstallCommand extends Command
{
    protected $signature = 'app:install
                            {--name=Administrator : Admin display name}
                            {--email=admin@example.com : Admin email}
                            {--password= : Admin password (prompted if omitted)}
                            {--force : Re-run seeders even if admin exists}';

    protected $description = 'Run migrations, seed reference data, and create the first administrator';

    public function handle(): int
    {
        $this->info('AV Asset Manager — installing…');

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        $email = (string) $this->option('email');
        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->option('force')) {
            $existing->assignRole('admin');
            $this->warn("User {$email} already exists; ensured admin role.");
            $this->info('Install complete.');

            return self::SUCCESS;
        }

        $password = $this->option('password') ?: $this->secret('Admin password');

        $validator = Validator::make([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'theme' => 'light',
            ]
        );

        $user->assignRole('admin');

        $this->info("Administrator ready: {$user->email}");
        $this->info('Install complete.');

        return self::SUCCESS;
    }
}
