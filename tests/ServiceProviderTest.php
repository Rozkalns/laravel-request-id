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

it('publishes the config file under the request-id-config tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(RequestIdServiceProvider::class, 'request-id-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/request-id.php')
        ->and(File::exists((string) array_key_first($paths)))->toBeTrue();
});

it('lets the app override the published config', function (): void {
    config(['request-id.project_code' => 'APP']);

    expect(config('request-id.project_code'))->toBe('APP')
        ->and(config('request-id.server_code'))->toBe('XXX');
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
    $kernel->setGlobalMiddleware([AppMiddleware::class, ...$kernel->getGlobalMiddleware()]);
    $kernel->setGlobalMiddleware(array_values(array_diff($kernel->getGlobalMiddleware(), [AssignRequestId::class])));

    new RequestIdServiceProvider(app())->boot();

    expect($kernel->getGlobalMiddleware()[0])->toBe(AssignRequestId::class)
        ->and($kernel->getGlobalMiddleware()[1])->toBe(AppMiddleware::class);
});

it('loads its views and translations under the request-id namespace', function (): void {
    expect(view()->exists('request-id::components.details'))->toBeTrue()
        ->and(__('request-id::errors.copy'))->toBe('Copy');
});
