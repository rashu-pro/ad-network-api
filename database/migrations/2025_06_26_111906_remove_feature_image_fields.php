<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */

    public function up(): void
    {
        // Remove column from publisher_assets
        Schema::table('publisher_assets', function (Blueprint $table) {
            if (Schema::hasColumn('publisher_assets', 'feature_image_url')) {
                $table->dropColumn('feature_image_url');
            }
        });

        // Drop publisher_asset_images table if exists
        if (Schema::hasTable('publisher_asset_images')) {
            Schema::dropIfExists('publisher_asset_images');
        }
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('publisher_assets', function (Blueprint $table) {
            $table->string('feature_image_url')->nullable(); // re-add if rolling back
        });

        Schema::create('publisher_asset_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_asset_id')->constrained('publisher_assets')->onDelete('cascade');
            $table->string('image_url');
            $table->timestamps();
        });
    }
};
