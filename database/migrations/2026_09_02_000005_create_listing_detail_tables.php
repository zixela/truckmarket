<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_truck_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('deal', ['sell', 'rent'])->default('sell');
            $table->string('make_model')->nullable();
            $table->enum('cab_type', ['sleeper', 'day_cab'])->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage')->nullable();

            $table->index(['deal', 'year']);
        });

        Schema::create('listing_trailer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('deal', ['sell', 'rent'])->default('sell');
            $table->enum('trailer_type', ['flatbed', 'reefer', 'dry_van'])->nullable();
            $table->unsignedSmallInteger('year')->nullable();
        });

        Schema::create('listing_load_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('load_type', ['car_hauler', 'flatbed', 'reefer', 'dry_van'])->nullable();
            $table->string('pickup_zip', 16)->nullable();
            $table->decimal('pickup_latitude', 9, 6)->nullable();
            $table->decimal('pickup_longitude', 9, 6)->nullable();
            $table->string('delivery_zip', 16)->nullable();
            $table->decimal('delivery_latitude', 9, 6)->nullable();
            $table->decimal('delivery_longitude', 9, 6)->nullable();
            $table->enum('vehicle_type', ['sedan', 'suv', 'truck'])->nullable();
            $table->unsignedInteger('weight')->nullable();

            $table->index('pickup_zip');
            $table->index('delivery_zip');
        });

        Schema::create('listing_company_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('services')->nullable();
        });

        Schema::create('listing_dispatcher_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time'])->nullable();
            $table->json('languages')->nullable();
        });

        Schema::create('listing_driver_owner_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->enum('cdl_class', ['a', 'b'])->nullable();
            $table->boolean('owns_truck')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_driver_owner_details');
        Schema::dropIfExists('listing_dispatcher_details');
        Schema::dropIfExists('listing_company_details');
        Schema::dropIfExists('listing_load_details');
        Schema::dropIfExists('listing_trailer_details');
        Schema::dropIfExists('listing_truck_details');
    }
};
