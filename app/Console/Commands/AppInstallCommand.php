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

        // #region agent log
        $logPath = base_path('debug-b5626b.log');
        file_put_contents($logPath, json_encode([
            'sessionId' => 'b5626b',
            'runId' => 'post-fix',
            'hypothesisId' => 'D',
            'location' => 'AppInstallCommand.php:handle',
            'message' => 'app:install starting migrate',
            'data' => [
                'driver' => \Illuminate\Support\Facades\DB::getDriverName(),
                'database' => (string) config('database.connections.'.config('database.default').'.database'),
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        try {
            $migrateExit = $this->call('migrate', ['--force' => true]);
            // #region agent log
            file_put_contents($logPath, json_encode([
                'sessionId' => 'b5626b',
                'runId' => 'post-fix',
                'hypothesisId' => 'D',
                'location' => 'AppInstallCommand.php:migrate-exit',
                'message' => 'migrate call returned',
                'data' => ['exit_code' => $migrateExit],
                'timestamp' => (int) (microtime(true) * 1000),
            ])."\n", FILE_APPEND);
            // #endregion
            if ($migrateExit !== self::SUCCESS) {
                return $migrateExit;
            }
        } catch (\Throwable $e) {
            // #region agent log
            file_put_contents($logPath, json_encode([
                'sessionId' => 'b5626b',
                'runId' => 'post-fix',
                'hypothesisId' => 'A',
                'location' => 'AppInstallCommand.php:migrate-catch',
                'message' => 'migrate failed during app:install',
                'data' => [
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'mentions_1059' => str_contains($e->getMessage(), '1059'),
                    'mentions_recipient_fk' => str_contains($e->getMessage(), 'notification_recipient_id_foreign')
                        || str_contains($e->getMessage(), 'notification_template_recipient_notification_recipient'),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ])."\n", FILE_APPEND);
            // #endregion
            throw $e;
        }

        // #region agent log
        file_put_contents($logPath, json_encode([
            'sessionId' => 'b5626b',
            'runId' => 'post-fix',
            'hypothesisId' => 'E',
            'location' => 'AppInstallCommand.php:after-migrate',
            'message' => 'migrate completed; checking email tables',
            'data' => [
                'has_recipients' => \Illuminate\Support\Facades\Schema::hasTable('notification_recipients'),
                'has_templates' => \Illuminate\Support\Facades\Schema::hasTable('notification_templates'),
                'has_pivot' => \Illuminate\Support\Facades\Schema::hasTable('notification_template_recipient'),
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

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
