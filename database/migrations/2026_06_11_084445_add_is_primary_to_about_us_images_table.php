<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_us_images', function (Blueprint $table) {
            if (! Schema::hasColumn('about_us_images', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('caption');
            }
        });
    }

    public function down(): void
    {
        Schema::table('about_us_images', function (Blueprint $table) {
            if (Schema::hasColumn('about_us_images', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }
};