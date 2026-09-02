<?php

use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->category = ServiceCategory::query()->create([
        'name_en' => 'Tire repair',
        'name_ru' => 'Шиномонтаж',
    ]);
});

function actingServiceUser(UserRole $role = UserRole::Driver): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('lets any user type create a service listing with a category', function (UserRole $role) {
    $user = actingServiceUser($role);

    test()->actingAs($user)->post('/en/account/listings', [
        'type' => 'service',
        'title' => 'Mobile tire service '.$role->value,
        'service_category_id' => test()->category->id,
        'zip' => '10001',
    ])->assertRedirect();

    $listing = Listing::query()->where('user_id', $user->id)->first();
    expect($listing->type)->toBe(ListingType::Service)
        ->and($listing->serviceDetail->service_category_id)->toBe(test()->category->id);
})->with([
    UserRole::Company,
    UserRole::Dispatcher,
    UserRole::Driver,
    UserRole::DriverOwner,
]);

it('requires a category for service listings', function () {
    $user = actingServiceUser();

    $this->actingAs($user)->post('/en/account/listings', [
        'type' => 'service',
        'title' => 'No category service',
    ])->assertSessionHasErrors('service_category_id');
});

it('rejects inactive categories', function () {
    $this->category->update(['is_active' => false]);
    $user = actingServiceUser();

    $this->actingAs($user)->post('/en/account/listings', [
        'type' => 'service',
        'title' => 'Inactive category service',
        'service_category_id' => $this->category->id,
    ])->assertSessionHasErrors('service_category_id');
});

it('filters the services page by category', function () {
    $other = ServiceCategory::query()->create(['name_en' => 'Towing', 'name_ru' => 'Эвакуатор']);

    $a = Listing::factory()->ofType(ListingType::Service)->create(['title' => 'Tire Masters']);
    $a->serviceDetail()->create(['service_category_id' => $this->category->id]);

    $b = Listing::factory()->ofType(ListingType::Service)->create(['title' => 'Tow Kings']);
    $b->serviceDetail()->create(['service_category_id' => $other->id]);

    $this->get('/en/services?service_category_id='.$this->category->id)
        ->assertOk()
        ->assertSee('Tire Masters')
        ->assertDontSee('Tow Kings');
});

it('shows the localized category name on the detail page', function () {
    $listing = Listing::factory()->ofType(ListingType::Service)->create(['title' => 'Best Tire Shop']);
    $listing->serviceDetail()->create(['service_category_id' => $this->category->id]);

    $this->get($listing->seoUrl('en'))->assertOk()->assertSee('Tire repair');
    $this->get($listing->seoUrl('ru'))->assertOk()->assertSee('Шиномонтаж');
});
