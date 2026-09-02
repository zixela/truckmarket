<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ZipCodeSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@truckmarket.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'locale' => 'en',
            ]
        );
        $admin->syncRoles([UserRole::Admin->value]);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
