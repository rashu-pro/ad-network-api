<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('advertiser_adserver_id');
            $table->string('campaign_name');
            $table->string('target_url')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed']);
            $table->enum('status', ['draft','publish', 'approve', 'conditionally_approve', 'conditionally_reject'])->default('draft');
            $table->text('note')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
}
