<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->boolean('is_positive');
            $table->text('body');
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['subject_id', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
