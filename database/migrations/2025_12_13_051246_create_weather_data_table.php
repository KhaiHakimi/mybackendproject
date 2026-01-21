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
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->nullable()->constrained('ports')->onDelete('set null');
            $table->dateTime('recorded_at');
            $table->decimal('wind_speed', 8, 2); // km/h
            $table->decimal('wave_height', 8, 2); // m
            $table->decimal('visibility', 8, 2)->nullable(); // km
            $table->decimal('tide_level', 8, 2)->nullable();
            $table->decimal('precipitation', 8, 2)->nullable(); // mm
            $table->decimal('risk_score', 5, 2)->default(0); // 0-100.00
            $table->string('risk_status')->default('Safe'); // Safe, Caution, High Risk
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
