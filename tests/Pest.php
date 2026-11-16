<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Tests\TestCase;

uses(TestCase::class)->in('.');

/**
 * @param  Closure(Request): array<string, string>  $rows
 */
function bindViewer(Closure $rows): void
{
    app()->bind(ErrorPageViewer::class, fn (): ErrorPageViewer => new readonly class($rows) implements ErrorPageViewer
    {
        public function __construct(private Closure $rows) {}

        public function rows(Request $request): array
        {
            return ($this->rows)($request);
        }
    });
}
