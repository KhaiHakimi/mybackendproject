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
            $table->string('image_path')->nullable()->after('name');
            $table->decimal('rating', 3, 1)->default(0)->after('image_path');
            $table->integer('reviews_count')->default(0)->after('rating');
            $table->string('length_ft')->nullable()->after('reviews_count');
            $table->json('amenities')->nullable()->after('capacity');
            $table->decimal('price', 10, 2)->default(0)->after('amenities');
            $table->text('description')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferries', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'rating',
                'reviews_count',
                'length_ft',
                'amenities',
                'price',
                'description',
            ]);
        });
    }
};
