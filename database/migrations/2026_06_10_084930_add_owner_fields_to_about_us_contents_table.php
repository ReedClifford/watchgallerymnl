<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_us_contents')) {
            return;
        }

        Schema::table('about_us_contents', function (Blueprint $table) {
            if (! Schema::hasColumn('about_us_contents', 'owner_image_path')) {
                $table->string('owner_image_path')->nullable()->after('dealer_message');
            }

            if (! Schema::hasColumn('about_us_contents', 'owner_bio')) {
                $table->text('owner_bio')->nullable()->after('owner_image_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('about_us_contents')) {
            return;
        }

        Schema::table('about_us_contents', function (Blueprint $table) {
            if (Schema::hasColumn('about_us_contents', 'owner_bio')) {
                $table->dropColumn('owner_bio');
            }

            if (Schema::hasColumn('about_us_contents', 'owner_image_path')) {
                $table->dropColumn('owner_image_path');
            }
        });
    }
};