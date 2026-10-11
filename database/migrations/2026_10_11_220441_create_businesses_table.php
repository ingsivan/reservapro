<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('timezone', 64)->default('America/Mexico_City');
            $table->unsignedSmallInteger('slot_interval_minutes')->default(30);
            $table->unsignedSmallInteger('min_notice_minutes')->default(60);
            $table->unsignedSmallInteger('booking_window_days')->default(30);
            $table->unsignedSmallInteger('cancellation_notice_hours')->default(2);
            $table->boolean('requires_confirmation')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
