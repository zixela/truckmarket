<?php

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ListingType::values());
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->string('zip', 16)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->enum('status', ListingStatus::values())->default(ListingStatus::Active->value);
            $table->text('moderation_note')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'created_at']);
            $table->index('zip');
            $table->index(['status', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
