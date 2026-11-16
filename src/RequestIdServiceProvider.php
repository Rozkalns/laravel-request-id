<?php

declare(strict_types=1);

namespace Rozkalns\RequestId;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Rozkalns\RequestId\Http\Middleware\AssignRequestId;
use Rozkalns\RequestId\Support\NoViewerRows;

final class RequestIdServiceProvider extends ServiceProvider
{
    private const array PAGES = ['layout', '403', '404', '419', '500', '503'];

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/request-id.php', 'request-id');

        $this->app->bind(ErrorPageViewer::class, NoViewerRows::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'request-id');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'request-id');
        Blade::componentNamespace('Rozkalns\\RequestId\\View\\Components', 'request-id');

        $this->publishes([
            __DIR__.'/../config/request-id.php' => config_path('request-id.php'),
        ], 'request-id-config');

        $this->publishes(
            array_combine(
                array_map(fn (string $page): string => __DIR__.'/../resources/stubs/errors/'.$page.'.blade.php', self::PAGES),
                array_map(fn (string $page): string => resource_path('views/errors/'.$page.'.blade.php'), self::PAGES),
            ),
            'request-id-pages',
        );

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->prependMiddleware(AssignRequestId::class);

        Event::listen(fn (CommandStarting $event): string => RequestId::ensure());
        Context::hydrated(fn (): string => RequestId::ensure());
    }
}
