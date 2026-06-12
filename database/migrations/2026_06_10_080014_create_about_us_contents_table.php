<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_us_contents', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow')->default('About Us');
            $table->string('title')->default('Meet your watch dealer');
            $table->text('body')->nullable();
            $table->string('dealer_name')->nullable();
            $table->text('dealer_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_us_contents');
    }
};