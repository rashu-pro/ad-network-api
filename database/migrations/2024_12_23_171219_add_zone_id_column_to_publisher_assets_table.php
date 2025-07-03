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
        Schema::table('publisher_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->integer('zone_adserver_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publisher_assets', function (Blueprint $table) {
            $table->dropColumn('zone_id');
            $table->dropColumn('zone_adserver_id');
        });
    }
};
