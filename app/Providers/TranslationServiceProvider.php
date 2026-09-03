<?php

namespace App\Providers;

use App\Services\Translation\DatabaseLoader;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as BaseTranslationServiceProvider;
use ReflectionClass;

/**
 * Replaces the framework's file loader with the database-backed one.
 * Listed after the framework provider, so it owns the deferred 'translator' services.
 */
class TranslationServiceProvider extends BaseTranslationServiceProvider
{
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $frameworkLang = dirname((new ReflectionClass(FileLoader::class))->getFileName()).'/lang';

            return new DatabaseLoader($app['files'], [$frameworkLang, $app['path.lang']]);
        });

        $this->app->alias('translation.loader', DatabaseLoader::class);
    }

    public function provides()
    {
        return [...parent::provides(), DatabaseLoader::class];
    }
}
