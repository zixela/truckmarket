<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hard guard: RefreshDatabase against the real MySQL DB wipes development data.
     * That happens when config is cached (config:cache) and phpunit.xml env is ignored.
     * Must run BEFORE setUpTraits(), because RefreshDatabase migrates inside it.
     */
    protected function setUpTraits(): array
    {
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'Tests must run on sqlite, got ['.config('database.default').']. '
                .'Stale config cache? Run: php artisan config:clear'
            );
        }

        return parent::setUpTraits();
    }
}
