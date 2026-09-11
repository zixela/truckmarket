<?php

use App\Models\Setting;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('hides the whole site from robots while block_robots is on', function () {
    Setting::query()->findOrFail('block_robots')->update(['value' => '1']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee("Disallow: /\n", false)
        ->assertDontSee('Sitemap:');

    $this->get('/en')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('publishes the normal rules and the sitemap once block_robots is off', function () {
    Setting::query()->findOrFail('block_robots')->update(['value' => '0']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml')
        ->assertDontSee("Disallow: /\n", false);

    $this->get('/en')->assertOk()->assertDontSee('name="robots"', false);
});
