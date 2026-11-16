<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Rozkalns\RequestId\RequestIdServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /** @var array<string, string>|null the error views the skeleton held before a test published its own */
    private ?array $skeletonErrorViews = null;

    protected function getPackageProviders($app): array
    {
        return [RequestIdServiceProvider::class];
    }

    protected function publishPages(): void
    {
        $directory = resource_path('views/errors');
        $this->skeletonErrorViews = [];

        foreach (File::files($directory) as $file) {
            $this->skeletonErrorViews[$file->getFilename()] = File::get($file->getPathname());
        }

        View::addNamespace('errors', $directory);

        $this->artisan('vendor:publish', ['--tag' => 'request-id-pages', '--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        if ($this->skeletonErrorViews !== null) {
            $directory = resource_path('views/errors');

            File::cleanDirectory($directory);

            foreach ($this->skeletonErrorViews as $name => $contents) {
                File::put($directory.'/'.$name, $contents);
            }
        }

        parent::tearDown();
    }
}
