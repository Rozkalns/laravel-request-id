<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Context;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Rozkalns\RequestId\RequestId;

const DEFAULT_WRAPPER_CLASS = 'mt-8 w-full max-w-sm border-t border-zinc-200 pt-4 dark:border-zinc-700';

it('renders the rows, the default copy button and the script', function (): void {
    app()->instance('request', Request::create('/some/page'));
    $id = RequestId::assign();

    $html = Blade::render('<x-request-id::details />');

    expect($html)->toContain('data-error-details')
        ->toContain('data-error-block')
        ->toContain('class="'.DEFAULT_WRAPPER_CLASS.'"')
        ->toContain('Request ID')
        ->toContain($id)
        ->toContain('/some/page')
        ->toContain('<button type="button" data-error-copy><span data-copy-label>Copy</span></button>')
        ->toContain('navigator.clipboard.writeText')
        ->toContain("copied: 'Copied'");
});

it('lists the ID first, then the time and the path, then the viewer rows', function (): void {
    app()->bind(ErrorPageViewer::class, fn (): ErrorPageViewer => new class implements ErrorPageViewer
    {
        public function rows(Request $request): array
        {
            return ['User' => 'Egle', 'Empty' => ''];
        }
    });
    RequestId::assign();

    $html = Blade::render('<x-request-id::details />');

    preg_match_all('/<dt class="text-zinc-400">([^<]+)<\/dt>/', $html, $labels);

    expect($labels[1])->toBe(['Request ID', 'Time', 'Address', 'User']);
});

it('renders nothing without a request ID', function (): void {
    Context::forget('request_id');

    expect(trim(Blade::render('<x-request-id::details />')))->toBe('');
});

it('uses a slotted button in place of the default', function (): void {
    RequestId::assign();

    $html = Blade::render('<x-request-id::details><x-slot:button><a data-error-copy href="#"><span data-copy-label>Go</span></a></x-slot:button></x-request-id::details>');

    expect($html)->toContain('<a data-error-copy href="#"><span data-copy-label>Go</span></a>')
        ->not->toContain('<button type="button" data-error-copy>');
});

it('takes the wrapper classes as a prop', function (): void {
    RequestId::assign();

    $html = Blade::render('<x-request-id::details class="my-wrapper" />');

    expect($html)->toContain('class="my-wrapper"')->not->toContain(DEFAULT_WRAPPER_CLASS);
});

it('escapes a path in the block', function (): void {
    app()->instance('request', Request::create('/<script>alert(1)</script>'));
    RequestId::assign();

    expect(Blade::render('<x-request-id::details />'))->not->toContain('<script>alert(1)')->toContain('&lt;script&gt;');
});
