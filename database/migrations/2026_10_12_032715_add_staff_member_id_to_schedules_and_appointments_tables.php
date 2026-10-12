<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schedules without a staff member are the business's opening hours.
     * Appointments without one belong to a business that works alone.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('staff_member_id')->nullable()->after('business_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('staff_member_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->index(['staff_member_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['staff_member_id', 'starts_at']);
            $table->dropConstrainedForeignId('staff_member_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_member_id');
        });
    }
};
