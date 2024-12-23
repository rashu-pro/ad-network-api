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
        Schema::table('campaign_mappings', function (Blueprint $table) {
            $table->integer('campaign_adserver_id')->nullable();
            $table->integer('banner_adserver_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_mappings', function (Blueprint $table) {
            //
        });
    }
};
