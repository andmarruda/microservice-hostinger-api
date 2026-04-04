<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute((int) config('ratelimit.global', 120))
                ->by($request->ip())
                ->response(fn () => response()->json(['message' => 'Too Many Requests.'], 429));
        });

        RateLimiter::for('authenticated', function (Request $request) {
            return $request->user()
                ? Limit::perMinute((int) config('ratelimit.authenticated', 300))->by($request->user()->id)
                : Limit::perMinute((int) config('ratelimit.global', 120))->by($request->ip());
        });

        RateLimiter::for('writes', function (Request $request) {
            $vpsId = $request->route('vpsId', 'unknown');
            $userId = $request->user()?->id ?? $request->ip();

            return Limit::perMinute((int) config('ratelimit.writes', 20))
                ->by("writes:{$userId}:{$vpsId}")
                ->response(fn () => response()->json(['message' => 'Too Many Requests.'], 429));
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) config('ratelimit.login', 10))
                ->by($request->ip())
                ->response(fn () => response()->json(['message' => 'Too Many Requests.'], 429));
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
