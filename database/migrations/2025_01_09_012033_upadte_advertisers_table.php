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
        Schema::table('advertisers', function (Blueprint $table) {
            // Remove unnecessary columns
            $table->dropColumn([
                'first_name',
                'last_name',
                'business_name',
                'advertiser_phone',
                'advertiser_website',
                'address',
                'subscription_plan_id'
            ]);

            // Update columns
            $table->string('password')->nullable()->change();

            // Add new column
            $table->uuid('secure_api_id')->after('id')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisers', function (Blueprint $table) {
            // Re-add the removed columns
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('business_name', 255)->nullable();
            $table->string('advertiser_phone', 255)->nullable();
            $table->string('advertiser_website', 255)->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('subscription_plan_id')->nullable();

            // Revert column changes
            $table->string('password')->nullable(false)->change();

            // Drop the added column
            $table->dropColumn('secure_api_id');
        });
    }
};
