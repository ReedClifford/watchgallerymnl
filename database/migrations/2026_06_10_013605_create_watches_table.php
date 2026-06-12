<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watches', function (Blueprint $table) {
            $table->id();

            $table->string('brand')->default('Seiko');
            $table->string('model_name');
            $table->string('reference_number')->nullable();
            $table->string('condition')->default('Brand New');

            $table->text('description')->nullable();

            $table->string('movement')->nullable();
            $table->string('case_size')->nullable();
            $table->string('case_material')->nullable();
            $table->string('dial_color')->nullable();
            $table->string('crystal')->nullable();
            $table->string('bracelet_or_strap')->nullable();
            $table->string('water_resistance')->nullable();
            $table->string('box_papers')->nullable();

            $table->decimal('capital_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('discounted_price', 12, 2)->nullable();

            $table->enum('status', [
                'available',
                'reserved',
                'sold',
                'hidden',
            ])->default('available');

            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->decimal('sold_price', 12, 2)->nullable();
            $table->date('date_sold')->nullable();
            $table->string('buyer_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watches');
    }
};