<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
        // Dates are immutable: $date->addDay() returns a new object instead of changing the original.
        Date::use(CarbonImmutable::class);

        // Outside production, fail loudly on N+1 lazy loading, unknown attributes
        // and attributes silently dropped by mass assignment.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Block migrate:fresh / db:wipe on production.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Swagger (/docs/api): adds a "Bearer token" field to try protected endpoints.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
