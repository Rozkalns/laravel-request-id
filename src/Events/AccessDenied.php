<?php

declare(strict_types=1);

namespace Rozkalns\RequestId\Events;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessDenied
{
    public function __construct(
        public Request $request,
        public Response $response,
    ) {}
}
