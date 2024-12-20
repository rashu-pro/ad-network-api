<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublisherAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publisher_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publisher_id');
            $table->unsignedBigInteger('asset_id');
            $table->string('publisher_adserver_id');
            $table->string('url')->nullable();
            $table->integer('min_duration_in_hour');
            $table->decimal('price_per_hour', 8, 2);
            $table->timestamps();

            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('cascade');
            $table->foreign('adserver_id')->references('id')->on('adservers')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('publisher_assets');
    }
}