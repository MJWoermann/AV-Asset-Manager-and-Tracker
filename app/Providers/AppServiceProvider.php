<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Location;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);

        Asset::observe(AuditableObserver::class);
        AssetConnection::observe(AuditableObserver::class);
        AssetInterface::observe(AuditableObserver::class);
        AssetList::observe(AuditableObserver::class);
        AssetListItem::observe(AuditableObserver::class);
        Location::observe(AuditableObserver::class);
        User::observe(AuditableObserver::class);

        Blade::if('feature', function (string $feature): bool {
            return auth()->user()?->hasFeature($feature) ?? false;
        });

        Blade::if('developerFeature', function (string $feature): bool {
            return auth()->user()?->hasDeveloperFeature($feature) ?? false;
        });

        $excelTemp = storage_path('framework/cache/laravel-excel');
        if (! is_dir($excelTemp)) {
            mkdir($excelTemp, 0755, true);
        }
    }
}
