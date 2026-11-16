<?php

declare(strict_types=1);

it('has the same keys in Latvian and English', function (): void {
    $lv = require __DIR__.'/../resources/lang/lv/errors.php';
    $en = require __DIR__.'/../resources/lang/en/errors.php';

    expect(array_keys($lv))->toBe(array_keys($en));

    foreach ([$lv, $en] as $texts) {
        expect(array_filter($texts, fn (string $text): bool => $text === ''))->toBe([]);
    }
});

it('has no school wording in the stub texts', function (): void {
    $texts = json_encode([
        require __DIR__.'/../resources/lang/lv/errors.php',
        require __DIR__.'/../resources/lang/en/errors.php',
    ], JSON_UNESCAPED_UNICODE);

    expect(mb_strtolower((string) $texts))->not->toContain('school')->not->toContain('skolas');
});
