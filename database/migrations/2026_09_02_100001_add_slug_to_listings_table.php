<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->index('slug');
        });

        DB::table('listings')->orderBy('id')->each(function ($listing) {
            DB::table('listings')->where('id', $listing->id)->update([
                'slug' => Str::slug($listing->title) ?: 'listing',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
