<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            'key' => 'google_analytics_id',
            'value' => null,
            'description' => 'Google Analytics 4 measurement ID (G-XXXXXXXXXX). Empty = tracking off.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'google_analytics_id')->delete();
    }
};
