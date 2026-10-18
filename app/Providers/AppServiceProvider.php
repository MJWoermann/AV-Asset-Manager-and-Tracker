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
use App\Support\AccessPermissions;
use App\Support\PerformanceLimits;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;
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
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $user->hasRole('admin') || AccessPermissions::isFeature($ability)) {
                return null;
            }

            if (AccessPermissions::isOperational($ability)) {
                return true;
            }

            return null;
        });

        Password::defaults(function () {
            return Password::min(8)->rules([
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (! preg_match('/\p{Lu}/u', $value)) {
                        $fail(__('The :attribute must contain at least one uppercase letter.'));
                    }

                    if (! preg_match('/\d|\p{Z}|\p{S}|\p{P}/u', $value)) {
                        $fail(__('The :attribute must contain at least one number or symbol.'));
                    }
                },
            ]);
        });

        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);

        Passport::authorizationView(function (array $parameters) {
            return view('mcp.authorize', $parameters);
        });

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

        $this->configurePerformanceRateLimiters();
    }

    protected function configurePerformanceRateLimiters(): void
    {
        RateLimiter::for('import', function (Request $request) {
            return Limit::perMinute(PerformanceLimits::int('throttle_import_per_minute'))
                ->by($this->rateLimitKey($request, 'import'));
        });

        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(PerformanceLimits::int('throttle_export_per_minute'))
                ->by($this->rateLimitKey($request, 'export'));
        });

        RateLimiter::for('compare', function (Request $request) {
            return Limit::perMinute(PerformanceLimits::int('throttle_compare_per_minute'))
                ->by($this->rateLimitKey($request, 'compare'));
        });

        RateLimiter::for('backup', function (Request $request) {
            return Limit::perMinute(PerformanceLimits::int('throttle_backup_per_minute'))
                ->by($this->rateLimitKey($request, 'backup'));
        });

        RateLimiter::for('mcp', function (Request $request) {
            return Limit::perMinute(60)->by($this->rateLimitKey($request, 'mcp'));
        });
    }

    protected function rateLimitKey(Request $request, string $bucket): string
    {
        $userId = $request->user()?->getAuthIdentifier();

        return $bucket.'|'.($userId !== null ? (string) $userId : $request->ip());
    }
}
