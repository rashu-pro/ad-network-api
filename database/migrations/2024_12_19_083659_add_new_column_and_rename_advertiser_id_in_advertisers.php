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
            $table->renameColumn('advertiser_id', 'adserver_id');
            $table->renameColumn('advertiser_name', 'first_name');
            $table->renameColumn('company_name', 'business_name');
            $table->string('last_name')->after('first_name');
            $table->string('subscription_id')->after('address')->nullable();
            $table->string('subscription_plan_id')->after('subscription_id')->nullable();
            $table->timestamp('subscription_expired_at')->after('subscription_plan_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisers', function (Blueprint $table) {
            $table->renameColumn('adserver_id', 'advertiser_id');
            $table->renameColumn('first_name', 'advertiser_name');
            $table->renameColumn('business_name', 'company_name');
            $table->dropColumn('last_name');
            $table->dropColumn('subscription_id');
            $table->dropColumn('subscription_plan_id');
            $table->dropColumn('subscription_expired_at');
        });
    }
};
