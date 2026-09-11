<?php

use App\Models\Setting;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('loads the Google Analytics tag only when a valid measurement ID is configured', function () {
    $this->get('/en')->assertOk()->assertDontSee('googletagmanager.com/gtag/js');

    Setting::query()->findOrFail('google_analytics_id')->update(['value' => 'G-ABC123XYZ']);

    $this->get('/en')
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
        ->assertSee("gtag('config', 'G-ABC123XYZ')", false);

    Setting::query()->findOrFail('google_analytics_id')->update(['value' => '<script>alert(1)</script>']);

    $this->get('/en')->assertOk()->assertDontSee('googletagmanager.com/gtag/js');
});
