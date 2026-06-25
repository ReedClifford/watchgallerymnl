<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (!Schema::hasColumn('watches', 'is_in_demand')) {
                $table->boolean('is_in_demand')->default(false)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (Schema::hasColumn('watches', 'is_in_demand')) {
                $table->dropColumn('is_in_demand');
            }
        });
    }
};