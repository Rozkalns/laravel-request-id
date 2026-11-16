<?php

declare(strict_types=1);

namespace Rozkalns\RequestId\Support;

use Illuminate\Http\Request;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;

final class NoViewerRows implements ErrorPageViewer
{
    #[\Override]
    public function rows(Request $request): array
    {
        return [];
    }
}
