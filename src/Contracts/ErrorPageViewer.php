<?php

declare(strict_types=1);

namespace Rozkalns\RequestId\Contracts;

use Illuminate\Http\Request;

interface ErrorPageViewer
{
    /**
     * @return array<string, string> label => value, with the labels already translated
     */
    public function rows(Request $request): array;
}
