<?php

use App\Services\Translation\TranslationSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64);
            $table->string('key', 191);
            $table->text('en')->nullable();
            $table->text('ru')->nullable();
            $table->text('en_default')->nullable();
            $table->text('ru_default')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        // Mirror lang/{en,ru}/*.php so the admin sees every site text right away.
        app(TranslationSync::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
