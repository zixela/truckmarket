<?php

use App\Enums\ListingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ru');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('listing_service_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();

            $table->index('service_category_id');
        });

        // Fresh installs (and sqlite tests) get the new enum value from the
        // create_listings_table migration; the live MySQL column needs an ALTER.
        if (DB::getDriverName() === 'mysql') {
            $values = "'".implode("','", ListingType::values())."'";
            DB::statement("ALTER TABLE listings MODIFY COLUMN type ENUM({$values}) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_service_details');
        Schema::dropIfExists('service_categories');
    }
};
