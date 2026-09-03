<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\TranslationServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TranslationServiceProvider::class,
];
