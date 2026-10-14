<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Location;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Asset::observe(AuditableObserver::class);
        AssetList::observe(AuditableObserver::class);
        AssetListItem::observe(AuditableObserver::class);
        Location::observe(AuditableObserver::class);
        User::observe(AuditableObserver::class);

        $excelTemp = storage_path('framework/cache/laravel-excel');
        if (! is_dir($excelTemp)) {
            mkdir($excelTemp, 0755, true);
        }
    }
}
