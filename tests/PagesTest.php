<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

const PAGES = ['layout', '403', '404', '419', '500', '503'];

beforeEach(function (): void {
    $this->publishPages();

    Route::get('/boom', fn () => throw new RuntimeException('SQLSTATE secret'));
    Route::get('/forbidden', fn () => abort(403, 'Plan 17 belongs to another group'));
    Route::get('/missing-model', fn () => throw (new ModelNotFoundException)->setModel('Secret\\Ledger', [17]));
    Route::get('/expired', fn () => abort(419));
});

it('copies the six stub files into the app views under the request-id-pages tag', function (): void {
    foreach (PAGES as $page) {
        expect(File::exists(resource_path('views/errors/'.$page.'.blade.php')))->toBeTrue($page);
    }
});

it('renders each published page with its code, title and the request ID block', function (string $path, int $status, string $title): void {
    $response = $this->get($path)->assertStatus($status);

    $response->assertSee($title)->assertSee('data-error-details', false)->assertSee($response->headers->get('X-Request-Id') ?? '');
})->with([
    '403' => ['/forbidden', 403, 'Access denied'],
    '404' => ['/nothing-here', 404, 'Page not found'],
    '419' => ['/expired', 419, 'Session expired'],
    '500' => ['/boom', 500, 'Server error'],
]);

it('renders the 503 page without a block when no request ID exists', function (): void {
    Context::forget('request_id');

    $html = view('errors::503')->render();

    expect($html)->toContain('Maintenance')->not->toContain('data-error-block');
});

it('names the app only through its config value and loads nothing from outside', function (): void {
    config(['app.name' => 'Probe App']);
    Context::forget('request_id');

    $html = view('errors::404')->render();

    expect($html)->toContain('Probe App')
        ->not->toContain('<script src')->not->toContain('<link ')->not->toContain('<img')->not->toContain('@import')->not->toContain('url(')
        ->toContain('prefers-color-scheme: dark')
        ->and(substr_count($html, '<style>'))->toBe(1)
        ->and(File::get(resource_path('views/errors/layout.blade.php')))->not->toContain('http');
});

it('shows no exception detail on the 500, 403 and missing-model 404 pages', function (string $path, int $status): void {
    $html = $this->get($path)->assertStatus($status)->getContent();

    expect($html)
        ->not->toContain('SQLSTATE')
        ->not->toContain('secret')
        ->not->toContain('Plan 17')
        ->not->toContain('another group')
        ->not->toContain('RuntimeException')
        ->not->toContain('ModelNotFoundException')
        ->not->toContain('Secret\\Ledger')
        ->not->toContain('Ledger')
        ->not->toContain('HttpException')
        ->not->toContain('Stack trace')
        ->not->toContain(base_path());
})->with([
    '500' => ['/boom', 500],
    '403' => ['/forbidden', 403],
    '404 missing model' => ['/missing-model', 404],
]);
