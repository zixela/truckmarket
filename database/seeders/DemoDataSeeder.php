<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\ZipCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = [];

        foreach (UserRole::registerable() as $i => $role) {
            $users[$role->value] = User::query()->create([
                'name' => ucfirst(str_replace('_', ' ', $role->value)).' Demo',
                'email' => $role->value.'@truckmarket.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_name' => $role === UserRole::Company ? 'Vmoon Corporation' : null,
                'zip' => ['10001', '60601', '90001', '77001'][$i],
                'locale' => 'en',
            ]);
            $users[$role->value]->assignRole($role->value);
        }

        $samples = [
            [ListingType::Truck, 'Freightliner Cascadia 2020', 45000, '10001', ['deal' => 'sell', 'make_model' => 'Freightliner Cascadia', 'cab_type' => 'sleeper', 'year' => 2020, 'mileage' => 480000]],
            [ListingType::Truck, 'Volvo VNL 760 for rent', 1200, '60601', ['deal' => 'rent', 'make_model' => 'Volvo VNL 760', 'cab_type' => 'sleeper', 'year' => 2019, 'mileage' => 620000]],
            [ListingType::Trailer, 'Great Dane Reefer 53ft', 32000, '90001', ['deal' => 'sell', 'trailer_type' => 'reefer', 'year' => 2018]],
            [ListingType::Load, 'Chicago → Dallas car hauling', 2200, '60601', ['load_type' => 'car_hauler', 'pickup_zip' => '60601', 'delivery_zip' => '75201', 'vehicle_type' => 'sedan', 'weight' => 8000]],
            [ListingType::Load, 'Miami → Atlanta reefer load', 1800, '33101', ['load_type' => 'reefer', 'pickup_zip' => '33101', 'delivery_zip' => '30301', 'vehicle_type' => 'truck', 'weight' => 24000]],
            [ListingType::Company, 'Vmoon Corporation — carrier services', null, '10001', ['company_name' => 'Vmoon Corporation', 'services' => 'Dry van, reefer, nationwide']],
            [ListingType::Dispatcher, 'Experienced dispatcher, English C1', null, '60601', ['experience_years' => 5, 'employment_type' => 'full_time', 'languages' => ['english_c1', 'russian']]],
            [ListingType::DriverOwner, 'Owner-operator, CDL A, 7 years', null, '77001', ['experience_years' => 7, 'cdl_class' => 'a', 'owns_truck' => true]],
        ];

        $roleKeys = array_keys($users);
        $created = [];

        foreach ($samples as $i => [$type, $title, $price, $zip, $detail]) {
            $owner = $users[$roleKeys[$i % count($roleKeys)]];
            $coords = ZipCode::find($zip);

            $listing = Listing::query()->create([
                'user_id' => $owner->id,
                'type' => $type,
                'title' => $title,
                'description' => 'Demo listing seeded for development.',
                'price' => $price,
                'zip' => $zip,
                'latitude' => $coords?->latitude,
                'longitude' => $coords?->longitude,
            ]);

            if ($type === ListingType::Load) {
                $pickup = ZipCode::find($detail['pickup_zip']);
                $delivery = ZipCode::find($detail['delivery_zip']);
                $detail['pickup_latitude'] = $pickup?->latitude;
                $detail['pickup_longitude'] = $pickup?->longitude;
                $detail['delivery_latitude'] = $delivery?->latitude;
                $detail['delivery_longitude'] = $delivery?->longitude;
            }

            $listing->{$listing->detailRelation()}()->create($detail);
            $created[] = $listing;
        }

        // A completed order with a review, and one pending order awaiting confirmation.
        $truck = $created[0];
        $customer = $users[UserRole::Dispatcher->value];

        if ($customer->id !== $truck->user_id) {
            $completed = Order::query()->create([
                'listing_id' => $truck->id,
                'customer_id' => $customer->id,
                'owner_id' => $truck->user_id,
                'status' => OrderStatus::Completed,
                'message' => 'Interested in this truck.',
                'confirmed_at' => now()->subDays(3),
                'completed_at' => now()->subDay(),
            ]);

            Review::query()->create([
                'order_id' => $completed->id,
                'author_id' => $customer->id,
                'subject_id' => $truck->user_id,
                'score' => 5,
                'is_positive' => true,
                'body' => 'Great communication. Everything was clear.',
            ]);
        }

        $load = $created[3];
        $loadCustomer = $users[UserRole::Driver->value];

        if ($loadCustomer->id !== $load->user_id) {
            Order::query()->create([
                'listing_id' => $load->id,
                'customer_id' => $loadCustomer->id,
                'owner_id' => $load->user_id,
                'status' => OrderStatus::Pending,
                'message' => 'I can pick this up tomorrow morning.',
            ]);
        }
    }
}
