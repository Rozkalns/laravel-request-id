<?php

declare(strict_types=1);

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rozkalns\RequestId\Events\AccessDenied;

beforeEach(function (): void {
    $this->denials = [];
    $this->logged = [];

    Event::listen(AccessDenied::class, function (AccessDenied $event): void {
        $this->denials[] = $event;
    });
    Event::listen(MessageLogged::class, function (MessageLogged $event): void {
        $this->logged[] = $event;
    });

    Route::get('/forbidden', fn () => abort(403, 'Plan 17 belongs to another group'));
    Route::get('/open', fn (): string => 'ok');
    Route::get('/expired', fn () => abort(419));
});

it('fires once for a 403 with the request and the response', function (): void {
    $this->get('/forbidden')->assertForbidden();

    expect($this->denials)->toHaveCount(1)
        ->and($this->denials[0]->request->path())->toBe('forbidden')
        ->and($this->denials[0]->response->getStatusCode())->toBe(403);
});

it('never fires for another status', function (string $path, int $status): void {
    $this->get($path)->assertStatus($status);

    expect($this->denials)->toBe([]);
})->with([
    '200' => ['/open', 200],
    '404' => ['/missing', 404],
    '419' => ['/expired', 419],
]);

it('writes no log line of its own for a 403', function (): void {
    $this->get('/forbidden')->assertForbidden();

    expect($this->logged)->toBe([]);
});
