<?php

declare(strict_types=1);

namespace Rozkalns\RequestId;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Livewire\Livewire;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class ErrorPageDetails
{
    private const string MASK = '…';

    /**
     * @param  array<string, string>  $rows  the viewer's rows, label => value
     */
    public function __construct(
        public string $requestId,
        public string $time,
        public string $path,
        public array $rows = [],
    ) {}

    public static function current(): ?self
    {
        $requestId = RequestId::current();

        if ($requestId === null) {
            return null;
        }

        try {
            $time = self::time();
            $path = self::path(request());
        } catch (Throwable) {
            return new self($requestId, '', '/');
        }

        try {
            $rows = resolve(ErrorPageViewer::class)->rows(request());
        } catch (Throwable) {
            $rows = [];
        }

        return new self($requestId, $time, $path, $rows);
    }

    public static function path(Request $request): string
    {
        $isLivewire = class_exists(Livewire::class) && Livewire::isLivewireRequest();
        $path = '/'.ltrim($isLivewire ? Livewire::originalPath() : $request->path(), '/');
        $route = $isLivewire ? self::matchRoute($path, Livewire::originalMethod()) : $request->route();

        $secrets = [];

        if ($route instanceof Route) {
            foreach ($route->parameters() as $name => $value) {
                if (str_contains(strtolower((string) $name), 'token') && is_scalar($value)) {
                    $secrets[] = (string) $value;
                }
            }
        }

        $segments = array_map(fn (string $segment): string => in_array(rawurldecode($segment), $secrets, true) ? self::MASK : $segment, explode('/', $path));

        return implode('/', $segments);
    }

    private static function time(): string
    {
        $timezone = config('request-id.timezone') ?? config('app.timezone');

        return now(is_string($timezone) ? $timezone : null)->format(config()->string('request-id.time_format'));
    }

    private static function matchRoute(string $path, string $httpMethod): ?Route
    {
        try {
            return resolve(Router::class)->getRoutes()->match(Request::create($path, $httpMethod));
        } catch (HttpExceptionInterface) {
            return null;
        }
    }
}
