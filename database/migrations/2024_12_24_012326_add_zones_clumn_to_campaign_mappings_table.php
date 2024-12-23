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
            $table->unsignedBigInteger('publisher_zone_id')->nullable();
            $table->integer('publisher_zone_adserver_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_mappings', function (Blueprint $table) {
            $table->dropColumn('publisher_zone_id');
            $table->dropColumn('publisher_zone_adserver_id');
        });
    }
};
