<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'key' => 'admin_login_max_attempts',
                'value' => '4',
                'description' => 'Failed admin login attempts allowed before the lockout kicks in.',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'key' => 'admin_login_lockout_minutes',
                'value' => '5',
                'description' => 'How many minutes the admin login stays locked after too many failed attempts.',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['admin_login_max_attempts', 'admin_login_lockout_minutes'])
            ->delete();
    }
};
