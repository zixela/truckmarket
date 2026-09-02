<?php

use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Filament\Resources\Listings\Pages\EditListing;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::Admin->value);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
});

it('shows the matching detail fields and swaps them when the type changes', function () {
    $listing = Listing::factory()->ofType(ListingType::Truck)->create();
    $listing->truckDetail()->create(['deal' => 'sell', 'make_model' => 'Cascadia', 'year' => 2020]);

    Livewire::test(EditListing::class, ['record' => $listing->getRouteKey()])
        ->assertFormFieldVisible('truckDetail.make_model')
        ->assertFormFieldHidden('loadDetail.load_type')
        ->fillForm([
            'type' => ListingType::Load->value,
        ])
        ->assertFormFieldHidden('truckDetail.make_model')
        ->assertFormFieldVisible('loadDetail.load_type')
        ->fillForm([
            'loadDetail.load_type' => 'reefer',
            'loadDetail.pickup_zip' => '10001',
            'loadDetail.delivery_zip' => '90001',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $listing->refresh();

    expect($listing->type)->toBe(ListingType::Load)
        ->and($listing->loadDetail?->load_type)->toBe('reefer')
        ->and($listing->truckDetail()->exists())->toBeFalse();
});

it('manages listing photos from the admin edit form', function () {
    Storage::fake('public');

    $listing = Listing::factory()->ofType(ListingType::Truck)->create();
    $listing->truckDetail()->create(['deal' => 'sell']);

    Livewire::test(EditListing::class, ['record' => $listing->getRouteKey()])
        ->assertFormFieldVisible('photos')
        ->fillForm([
            'photos' => [UploadedFile::fake()->image('truck.jpg', 800, 600)],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($listing->refresh()->getMedia(Listing::PHOTO_COLLECTION))->toHaveCount(1);
});
