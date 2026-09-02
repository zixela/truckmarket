<?php

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('shows the home page with type counts in both locales', function (string $locale) {
    Listing::factory()->ofType(ListingType::Truck)->count(2)->create();

    $this->get("/{$locale}")
        ->assertOk()
        ->assertSee('trucks');
})->with(['en', 'ru']);

it('filters the marketplace by type on the SEO page', function () {
    Listing::factory()->ofType(ListingType::Truck)->create(['title' => 'Truck Alpha']);
    Listing::factory()->ofType(ListingType::Load)->create(['title' => 'Load Beta']);

    $this->get('/en/trucks')
        ->assertOk()
        ->assertSee('Truck Alpha')
        ->assertDontSee('Load Beta');
});

it('redirects the legacy marketplace URL to the SEO type page', function () {
    $this->get('/en/marketplace?type=load')
        ->assertRedirect('/en/loads')
        ->assertStatus(301);
});

it('serves the detail page on its canonical slug URL', function () {
    $listing = Listing::factory()->ofType(ListingType::Truck)->create(['title' => 'Freightliner Cascadia 2020']);

    expect($listing->slug)->toBe('freightliner-cascadia-2020');

    $this->get("/en/trucks/freightliner-cascadia-2020-{$listing->id}")
        ->assertOk()
        ->assertSee('Freightliner Cascadia 2020');
});

it('301-redirects stale or wrong slugs to the canonical URL', function () {
    $listing = Listing::factory()->ofType(ListingType::Truck)->create(['title' => 'Volvo VNL 760']);

    $canonical = $listing->seoUrl('en');

    $this->get("/en/trucks/old-title-{$listing->id}")->assertRedirect($canonical)->assertStatus(301);
    $this->get("/en/loads/volvo-vnl-760-{$listing->id}")->assertRedirect($canonical)->assertStatus(301);
    $this->get("/en/listings/{$listing->id}")->assertRedirect($canonical)->assertStatus(301);
});

it('hides inactive listings from the public detail page', function () {
    $listing = Listing::factory()->ofType(ListingType::Truck)->inactive()->create();

    $this->get("/en/trucks/{$listing->slug}-{$listing->id}")->assertNotFound();
});

it('redirects the root to a localized home', function () {
    $this->get('/')->assertRedirect();
});

it('shows a public profile', function () {
    $user = User::factory()->create();

    $this->get("/en/users/{$user->id}")->assertOk()->assertSee($user->name);
});
