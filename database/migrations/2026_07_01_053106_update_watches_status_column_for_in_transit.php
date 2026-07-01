<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('watches')
            ->where('status', 'hidden')
            ->update([
                'status' => 'available',
                'is_visible' => false,
            ]);

        DB::statement("
            ALTER TABLE watches
            MODIFY status ENUM('available', 'reserved', 'in_transit', 'sold')
            NOT NULL DEFAULT 'available'
        ");
    }

    public function down(): void
    {
        DB::table('watches')
            ->where('status', 'in_transit')
            ->update(['status' => 'available']);

        DB::statement("
            ALTER TABLE watches
            MODIFY status ENUM('available', 'reserved', 'sold')
            NOT NULL DEFAULT 'available'
        ");
    }
};