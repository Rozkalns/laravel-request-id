<?php

declare(strict_types=1);

namespace Rozkalns\RequestId;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Rozkalns\RequestId\Http\Middleware\AssignRequestId;
use Rozkalns\RequestId\Support\NoViewerRows;

final class RequestIdServiceProvider extends ServiceProvider
{
    public const array PAGES = ['layout', '403', '404', '419', '500', '503'];

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
        $this->callAfterResolving(BladeCompiler::class, fn (BladeCompiler $blade) => $blade->componentNamespace('Rozkalns\\RequestId\\View\\Components', 'request-id'));
        $this->callAfterResolving(Kernel::class, fn (HttpKernel $kernel) => $kernel->prependMiddleware(AssignRequestId::class));

        if ($this->app->runningInConsole()) {
            $pages = [];

            foreach (self::PAGES as $page) {
                $pages[__DIR__.'/../resources/stubs/errors/'.$page.'.blade.php'] = resource_path('views/errors/'.$page.'.blade.php');
            }

            $this->publishes([__DIR__.'/../config/request-id.php' => config_path('request-id.php')], 'request-id-config');
            $this->publishes($pages, 'request-id-pages');

            Event::listen(CommandStarting::class, fn (): string => RequestId::ensure());
        }

        Context::hydrated(fn (): string => RequestId::ensure());
    }
}
