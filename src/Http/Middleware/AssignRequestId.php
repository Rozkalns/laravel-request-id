<?php

declare(strict_types=1);

namespace Rozkalns\RequestId\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rozkalns\RequestId\Events\AccessDenied;
use Rozkalns\RequestId\RequestId;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = RequestId::assign();

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        if ($response->getStatusCode() === 403) {
            event(new AccessDenied($request, $response));
        }

        return $response;
    }
}
