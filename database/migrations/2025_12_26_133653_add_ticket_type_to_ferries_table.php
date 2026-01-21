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
        Schema::table('ferries', function (Blueprint $table) {
            // Options: 'Online Booking', 'Walk-in / Counter', 'Both Available'
            $table->string('ticket_type')->default('Walk-in / Counter')->after('booking_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferries', function (Blueprint $table) {
            $table->dropColumn('ticket_type');
        });
    }
};
