<?php

declare(strict_types=1);

namespace Rozkalns\RequestId;

use Illuminate\Support\Facades\Context;
use Sentry\State\Scope;

final class RequestId
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const int LENGTH = 10;

    public const string KEY = 'request_id';

    public static function assign(): string
    {
        return self::use(self::generate());
    }

    public static function ensure(): string
    {
        $inherited = self::current();

        return $inherited === null ? self::assign() : self::use($inherited);
    }

    public static function current(): ?string
    {
        $id = Context::get(self::KEY);

        return is_string($id) ? $id : null;
    }

    private static function use(string $id): string
    {
        Context::add(self::KEY, $id);

        if (function_exists('Sentry\configureScope')) {
            \Sentry\configureScope(fn (Scope $scope): Scope => $scope->setTag(self::KEY, $id));
        }

        return $id;
    }

    private static function generate(): string
    {
        $bytes = random_bytes(self::LENGTH);
        $id = config()->string('request-id.server_code').config()->string('request-id.project_code').'-';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= self::ALPHABET[ord($bytes[$i]) & 31];
        }

        return $id;
    }
}
