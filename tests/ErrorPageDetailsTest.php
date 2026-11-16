<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Rozkalns\RequestId\ErrorPageDetails;
use Rozkalns\RequestId\RequestId;

function matchedRequest(string $uri, string $pattern): Request
{
    $route = Route::get($pattern, fn (): null => null);
    $request = Request::create($uri);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

function livewireRequest(string $pagePath, string $pageMethod = 'GET'): Request
{
    $snapshot = json_encode(['memo' => ['path' => $pagePath, 'method' => $pageMethod]], JSON_THROW_ON_ERROR);
    $request = Request::create('/livewire/update', 'POST', ['components' => [['snapshot' => $snapshot]]]);
    $request->headers->set('X-Livewire', 'true');

    app()->instance('request', $request);

    return $request;
}

it('is null without a request ID', function (): void {
    Context::forget('request_id');

    expect(ErrorPageDetails::current())->toBeNull();
});

it('holds the ID, the time in the configured format and zone, and the path', function (): void {
    config(['request-id.timezone' => 'Pacific/Auckland', 'request-id.time_format' => 'Y']);
    app()->instance('request', Request::create('/some/page?x=1'));
    $id = RequestId::assign();

    $details = ErrorPageDetails::current();

    expect($details)->toBeInstanceOf(ErrorPageDetails::class)
        ->and($details->requestId)->toBe($id)
        ->and($details->time)->toBe(now('Pacific/Auckland')->format('Y'))
        ->and($details->path)->toBe('/some/page')
        ->and($details->rows)->toBe([]);
});

it('uses the app timezone when none is set', function (): void {
    config(['app.timezone' => 'Asia/Tokyo', 'request-id.time_format' => 'e']);
    RequestId::assign();

    expect(ErrorPageDetails::current()?->time)->toBe('Asia/Tokyo');
});

it('drops the query string and masks every token parameter whatever its case', function (string $uri, string $pattern, string $shown): void {
    expect(ErrorPageDetails::path(matchedRequest($uri, $pattern)))->toBe($shown);
})->with([
    'public link' => ['/b/SECRET?x=1', '/b/{token}', '/b/…'],
    'camel case' => ['/s/SECRET/edit', '/s/{cycleToken}/edit', '/s/…/edit'],
    'upper case' => ['/p/SECRET', '/p/{PreviewTOKEN}', '/p/…'],
    'no token' => ['/students/abc?search=Rūta', '/students/{student}', '/students/abc'],
]);

it('shows an unmatched path as typed, without its query string', function (): void {
    expect(ErrorPageDetails::path(Request::create('/nothing/here?q=1')))->toBe('/nothing/here');
});

it('shows the page path of a Livewire update and masks its token', function (): void {
    Route::get('/b/{token}', fn (): null => null);
    $request = livewireRequest('b/SECRET');

    expect(ErrorPageDetails::path($request))->toBe('/b/…');
});

it('shows the page path of a Livewire update on a route that does not exist', function (): void {
    $request = livewireRequest('nothing/here');

    expect(ErrorPageDetails::path($request))->toBe('/nothing/here');
});

it('adds the rows of a bound viewer after the ID, the time and the path', function (): void {
    app()->bind(ErrorPageViewer::class, fn (): ErrorPageViewer => new class implements ErrorPageViewer
    {
        public function rows(Request $request): array
        {
            return ['User' => 'Egle', 'Role' => 'Teacher'];
        }
    });
    RequestId::assign();

    expect(ErrorPageDetails::current()?->rows)->toBe(['User' => 'Egle', 'Role' => 'Teacher']);
});

it('falls back to the ID, the time and the path when the viewer throws', function (): void {
    app()->bind(ErrorPageViewer::class, fn (): ErrorPageViewer => new class implements ErrorPageViewer
    {
        public function rows(Request $request): array
        {
            throw new RuntimeException('database is down');
        }
    });
    $id = RequestId::assign();

    $details = ErrorPageDetails::current();

    expect($details?->requestId)->toBe($id)
        ->and($details?->time)->not->toBeEmpty()
        ->and($details?->path)->not->toBeEmpty()
        ->and($details?->rows)->toBe([]);
});

it('falls back to the ID alone when the time cannot be built', function (): void {
    config(['request-id.timezone' => 'Not/A_Zone']);
    $id = RequestId::assign();

    $details = ErrorPageDetails::current();

    expect($details?->requestId)->toBe($id)
        ->and($details?->time)->toBe('')
        ->and($details?->path)->toBe('/');
});
