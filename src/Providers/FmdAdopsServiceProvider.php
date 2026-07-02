<?php

declare(strict_types=1);

namespace Agenciafmd\FmdAdops\Providers;

use Illuminate\Support\ServiceProvider;
use Override;

final class FmdAdopsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    #[Override]
    public function register(): void
    {
        $this->registerConfigs();
    }

    private function registerConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-fmd-adops.php', 'laravel-fmd-adops');
    }
}
