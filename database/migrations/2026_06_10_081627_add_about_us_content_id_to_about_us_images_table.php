<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_us_images')) {
            return;
        }

        Schema::table('about_us_images', function (Blueprint $table) {
            if (! Schema::hasColumn('about_us_images', 'about_us_content_id')) {
                $table->unsignedBigInteger('about_us_content_id')
                    ->nullable()
                    ->after('id');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | If your old table used about_us_id, copy its values
        |--------------------------------------------------------------------------
        */
        if (Schema::hasColumn('about_us_images', 'about_us_id')) {
            DB::statement('
                UPDATE about_us_images
                SET about_us_content_id = about_us_id
                WHERE about_us_content_id IS NULL
            ');
        }

        /*
        |--------------------------------------------------------------------------
        | Attach old orphan images to the first About Us content row
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('about_us_contents')) {
            $aboutUsId = DB::table('about_us_contents')->value('id');

            if ($aboutUsId) {
                DB::table('about_us_images')
                    ->whereNull('about_us_content_id')
                    ->update([
                        'about_us_content_id' => $aboutUsId,
                    ]);
            }
        }

        Schema::table('about_us_images', function (Blueprint $table) {
            $table->foreign('about_us_content_id', 'about_us_images_content_fk')
                ->references('id')
                ->on('about_us_contents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('about_us_images')) {
            return;
        }

        Schema::table('about_us_images', function (Blueprint $table) {
            if (Schema::hasColumn('about_us_images', 'about_us_content_id')) {
                $table->dropForeign('about_us_images_content_fk');
                $table->dropColumn('about_us_content_id');
            }
        });
    }
};