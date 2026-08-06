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
        Schema::dropIfExists('reviews');

        Schema::table('ferries', function (Blueprint $table) {
            $table->dropColumn(['rating', 'reviews_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferries', function (Blueprint $table) {
            $table->decimal('rating', 3, 1)->default(0)->after('image_path');
            $table->integer('reviews_count')->default(0)->after('rating');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ferry_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->string('source')->default('internal');
            $table->timestamps();
        });
    }
};
