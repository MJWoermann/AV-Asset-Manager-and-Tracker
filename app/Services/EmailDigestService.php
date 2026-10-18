<?php

namespace App\Services;

use App\Enums\NotificationEventKey;
use App\Models\Asset;
use App\Support\EmailIntegrationSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmailDigestService
{
    public function __construct(
        private NotificationDispatchService $notifications,
    ) {}

    /**
     * @return array{sent: int, skipped: int, warnings: list<string>}
     */
    public function sendEnabledDigests(): array
    {
        if (! EmailIntegrationSettings::isEnabled()) {
            return ['sent' => 0, 'skipped' => 0, 'warnings' => ['Email integration is disabled.']];
        }

        $settings = EmailIntegrationSettings::get();
        $daysAhead = max(1, (int) $settings['digest_days_ahead']);
        $sent = 0;
        $skipped = 0;
        $warnings = [];

        if ($settings['digest_warranty_enabled']) {
            $result = $this->dispatchForAssets(
                NotificationEventKey::WarrantyExpiryDigest,
                $this->assetsWithExpiryWithin('warranty_expiry', $daysAhead),
                ['digest.days_ahead' => (string) $daysAhead],
            );
            $sent += $result['sent'];
            $skipped += $result['skipped'];
            $warnings = array_merge($warnings, $result['warnings']);
        }

        if ($settings['digest_test_tag_enabled']) {
            $result = $this->dispatchForAssets(
                NotificationEventKey::TestTagExpiryDigest,
                $this->assetsWithExpiryWithin('test_tag_expiry', $daysAhead),
                ['digest.days_ahead' => (string) $daysAhead],
            );
            $sent += $result['sent'];
            $skipped += $result['skipped'];
            $warnings = array_merge($warnings, $result['warnings']);
        }

        if ($settings['digest_low_stock_enabled']) {
            $result = $this->dispatchForAssets(
                NotificationEventKey::LowStockDigest,
                $this->lowStockAssets(),
            );
            $sent += $result['sent'];
            $skipped += $result['skipped'];
            $warnings = array_merge($warnings, $result['warnings']);
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'warnings' => $warnings];
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @param  array<string, string>  $context
     * @return array{sent: int, skipped: int, warnings: list<string>}
     */
    protected function dispatchForAssets(
        NotificationEventKey $event,
        Collection $assets,
        array $context = [],
    ): array {
        $sent = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($assets as $asset) {
            $result = $this->notifications->dispatch($event, $asset, null, $context);

            if ($result['sent']) {
                $sent++;
            } else {
                $skipped++;
                if (filled($result['warning'])) {
                    $warnings[] = $result['warning'];
                }
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'warnings' => $warnings];
    }

    /**
     * Assets whose expiry is on or before today + daysAhead (includes overdue).
     *
     * @return Collection<int, Asset>
     */
    public function assetsWithExpiryWithin(string $column, int $daysAhead): Collection
    {
        $cutoff = Carbon::today()->addDays($daysAhead);

        return Asset::query()
            ->whereNotNull($column)
            ->whereDate($column, '<=', $cutoff)
            ->orderBy($column)
            ->orderBy('id')
            ->get();
    }

    /**
     * Stock-tracked assets below their configured minimum.
     *
     * @return Collection<int, Asset>
     */
    public function lowStockAssets(): Collection
    {
        return Asset::query()
            ->whereHas('itemType', fn (Builder $query) => $query->where('supports_stock_tracking', true))
            ->whereNotNull('min_stock_level')
            ->whereColumn('quantity', '<', 'min_stock_level')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
