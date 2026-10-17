<?php

namespace App\Console\Commands;

use App\Support\PerformanceLimits;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class PerformanceLimitsCommand extends Command
{
    protected $signature = 'app:performance-limits
                            {action=show : show|set|reset|keys}
                            {key? : Limit key (for set/reset)}
                            {value? : New value (for set)}';

    protected $description = 'Show or update soft performance limits (import/export/compare/backup)';

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        try {
            return match ($action) {
                'show', 'list' => $this->showLimits(),
                'keys' => $this->listKeys(),
                'set' => $this->setLimit(),
                'reset' => $this->resetLimit(),
                default => $this->invalidAction($action),
            };
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function showLimits(): int
    {
        $defaults = PerformanceLimits::defaults();
        $current = PerformanceLimits::all();

        $rows = [];
        foreach ($current as $key => $value) {
            $default = $defaults[$key];
            $rows[] = [
                $key,
                $this->formatValue($value),
                $this->formatValue($default),
                $value === $default ? 'config/.env' : 'settings override',
            ];
        }

        $this->table(['Key', 'Effective', 'Config default', 'Source'], $rows);
        $this->line('Set: php artisan app:performance-limits set <key> <value>');
        $this->line('Reset one: php artisan app:performance-limits reset <key>');
        $this->line('Reset all: php artisan app:performance-limits reset');

        return self::SUCCESS;
    }

    protected function listKeys(): int
    {
        foreach (PerformanceLimits::keys() as $key) {
            $this->line($key);
        }

        return self::SUCCESS;
    }

    protected function setLimit(): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if ($key === null || $value === null) {
            $this->error('Usage: php artisan app:performance-limits set <key> <value>');

            return self::FAILURE;
        }

        $updated = PerformanceLimits::put([(string) $key => $value]);
        $this->info(sprintf(
            'Updated %s = %s (stored in settings; overrides .env until reset).',
            $key,
            $this->formatValue($updated[(string) $key]),
        ));

        return self::SUCCESS;
    }

    protected function resetLimit(): int
    {
        $key = $this->argument('key');
        $updated = PerformanceLimits::reset($key !== null ? (string) $key : null);

        if ($key === null) {
            $this->info('Cleared all performance_limits overrides; using config/.env defaults.');
        } else {
            $this->info(sprintf(
                'Reset %s to config default %s.',
                $key,
                $this->formatValue($updated[(string) $key]),
            ));
        }

        return self::SUCCESS;
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Unknown action [{$action}]. Use show, set, reset, or keys.");

        return self::FAILURE;
    }

    protected function formatValue(int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
