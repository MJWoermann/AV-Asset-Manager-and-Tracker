<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\User;

/**
 * Central gate for consumable stock tracking UI / authenticated routes.
 *
 * Today: per-user developer feature `stock_consumables`.
 * Later promotion to core: change only this helper.
 */
class StockTracking
{
    public const FEATURE_KEY = 'stock_consumables';

    public static function enabledFor(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasDeveloperFeature(self::FEATURE_KEY);
    }

    /**
     * Authenticated Stock tab / mutation surface: feature + item type flag.
     */
    public static function availableForAsset(?User $user, Asset $asset): bool
    {
        if (! self::enabledFor($user)) {
            return false;
        }

        return (bool) $asset->itemType?->supports_stock_tracking;
    }

    /**
     * Guest Kanban paths do not use the profile flag; they still require a
     * stock-tracked asset (and a valid token). Use this when disabling the
     * feature globally later without rewriting guest controllers.
     */
    public static function guestSurfaceEnabled(): bool
    {
        return true;
    }
}
