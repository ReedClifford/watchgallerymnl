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

        /*
        |--------------------------------------------------------------------------
        | Add missing required columns safely
        |--------------------------------------------------------------------------
        | Your current about_us_images table is missing image_path, so we cannot use:
        | ->after('image_path') until image_path exists.
        */
        Schema::table('about_us_images', function (Blueprint $table) {
            if (! Schema::hasColumn('about_us_images', 'about_us_content_id')) {
                $table->unsignedBigInteger('about_us_content_id')
                    ->nullable()
                    ->after('id');
            }

            if (! Schema::hasColumn('about_us_images', 'image_path')) {
                $table->string('image_path')
                    ->nullable()
                    ->after('about_us_content_id');
            }

            if (! Schema::hasColumn('about_us_images', 'caption')) {
                $table->string('caption')
                    ->nullable()
                    ->after('image_path');
            }

            if (! Schema::hasColumn('about_us_images', 'sort_order')) {
                $table->unsignedInteger('sort_order')
                    ->default(0)
                    ->after('caption');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | If your old table used a different image column name, copy it to image_path
        |--------------------------------------------------------------------------
        */
        $possibleOldImageColumns = [
            'path',
            'file_path',
            'photo_path',
            'image',
            'image_url',
            'filename',
        ];

        foreach ($possibleOldImageColumns as $column) {
            if (
                Schema::hasColumn('about_us_images', $column) &&
                Schema::hasColumn('about_us_images', 'image_path')
            ) {
                DB::table('about_us_images')
                    ->whereNull('image_path')
                    ->whereNotNull($column)
                    ->update([
                        'image_path' => DB::raw($column),
                    ]);

                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Attach orphan images to first About Us content row
        |--------------------------------------------------------------------------
        */
        if (
            Schema::hasTable('about_us_contents') &&
            Schema::hasColumn('about_us_images', 'about_us_content_id')
        ) {
            $aboutUsId = DB::table('about_us_contents')->value('id');

            if ($aboutUsId) {
                DB::table('about_us_images')
                    ->whereNull('about_us_content_id')
                    ->update([
                        'about_us_content_id' => $aboutUsId,
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('about_us_images')) {
            return;
        }

        Schema::table('about_us_images', function (Blueprint $table) {
            if (Schema::hasColumn('about_us_images', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('about_us_images', 'caption')) {
                $table->dropColumn('caption');
            }

            if (Schema::hasColumn('about_us_images', 'image_path')) {
                $table->dropColumn('image_path');
            }

            if (Schema::hasColumn('about_us_images', 'about_us_content_id')) {
                $table->dropColumn('about_us_content_id');
            }
        });
    }
};