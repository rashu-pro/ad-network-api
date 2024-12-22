<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertiser_id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('publisher_id');
            $table->unsignedBigInteger('publisher_asset_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('calculated_price', 8, 2);
            $table->boolean('is_active');
            $table->timestamps();

            $table->foreign('advertiser_id')->references('id')->on('advertisers')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('cascade');
            $table->foreign('publisher_asset_id')->references('id')->on('publisher_assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_mappings');
    }
}