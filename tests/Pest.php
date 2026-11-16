<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(function (): void {
        config()->set('app.name', 'TestApp');
        config()->set('app.env', 'testing');
        config()->set('app.debug', false);
    })
    ->in('.');
