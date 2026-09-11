<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            'key' => 'block_robots',
            'value' => '1',
            'description' => 'Hide the site from search engines: robots.txt disallows everything and pages get noindex (1 = hidden, 0 = indexable).',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'block_robots')->delete();
    }
};
