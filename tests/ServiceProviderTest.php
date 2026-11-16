<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Rozkalns\RequestId\Http\Middleware\AssignRequestId;
use Rozkalns\RequestId\RequestIdServiceProvider;
use Rozkalns\RequestId\Support\NoViewerRows;
use Tests\Fixtures\AppMiddleware;

it('merges the config with its defaults', function (): void {
    expect(config('request-id'))->toBe([
        'server_code' => 'XXX',
        'project_code' => 'XXX',
        'timezone' => null,
        'time_format' => 'd.m.Y H:i:s',
    ]);
});

it('publishes the six stub pages under the request-id-pages tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(RequestIdServiceProvider::class, 'request-id-pages');

    expect($paths)->toHaveCount(count(RequestIdServiceProvider::PAGES));

    foreach (RequestIdServiceProvider::PAGES as $page) {
        $source = array_search(resource_path('views/errors/'.$page.'.blade.php'), $paths, true);

        expect($source)->toBeString()->toEndWith('resources/stubs/errors/'.$page.'.blade.php')
            ->and(File::exists((string) $source))->toBeTrue($page);
    }
});

it('publishes the config file under the request-id-config tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(RequestIdServiceProvider::class, 'request-id-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/request-id.php')
        ->and(File::exists((string) array_key_first($paths)))->toBeTrue();
});

it('binds a viewer with no rows by default', function (): void {
    $viewer = resolve(ErrorPageViewer::class);

    expect($viewer)->toBeInstanceOf(NoViewerRows::class)
        ->and($viewer->rows(Request::create('/')))->toBe([]);
});

it('puts its middleware first in the global stack', function (): void {
    /** @var HttpKernel $kernel */
    $kernel = resolve(Kernel::class);

    expect($kernel->getGlobalMiddleware()[0])->toBe(AssignRequestId::class);
});

it('stays first when the app has prepended its own middleware before the package boots', function (): void {
    /** @var HttpKernel $kernel */
    $kernel = resolve(Kernel::class);
    $kernel->setGlobalMiddleware([AppMiddleware::class]);

    new RequestIdServiceProvider(app())->boot();

    expect($kernel->getGlobalMiddleware()[0])->toBe(AssignRequestId::class)
        ->and($kernel->getGlobalMiddleware()[1])->toBe(AppMiddleware::class);
});

it('loads its views and translations under the request-id namespace', function (): void {
    expect(view()->exists('request-id::components.details'))->toBeTrue()
        ->and(__('request-id::errors.copy'))->toBe('Copy');
});
