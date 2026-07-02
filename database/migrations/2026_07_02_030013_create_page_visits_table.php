<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();

            $table->string('visitor_id', 120)->index();
            $table->string('session_id', 120)->index();

            $table->string('page_path', 500)->index();
            $table->string('page_url', 1000)->nullable();
            $table->string('page_title')->nullable();
            $table->string('page_type')->nullable()->index();

            $table->string('referrer', 1000)->nullable();
            $table->string('device')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('ip_hash')->nullable()->index();

            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('interactions_count')->default(0);

            $table->boolean('is_valid')->default(false)->index();
            $table->boolean('is_engaged')->default(false)->index();
            $table->boolean('is_bot')->default(false)->index();

            $table->timestamp('entered_at')->nullable()->index();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->timestamps();

            $table->index(['visitor_id', 'page_path', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};