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
        Schema::create('publisher_asset_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publisher_asset_id');
            $table->string('image_url');
            $table->timestamps();

            $table->foreign('publisher_asset_id')
                ->references('id')
                ->on('publisher_assets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('publisher_asset_images');
    }
};
