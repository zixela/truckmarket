<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zip_codes', function (Blueprint $table) {
            $table->string('zip', 16)->primary();
            $table->string('city');
            $table->string('state', 8);
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zip_codes');
    }
};
