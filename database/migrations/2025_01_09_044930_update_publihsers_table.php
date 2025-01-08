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
        Schema::table('publishers', function (Blueprint $table) {
            // Drop unnecessary columns
            $table->dropColumn([
                'guid',
                'name',
                'company_name',
                'company_address',
                'website_url',
                'latitude',
                'longitude',
                'logo_url',
                'package_id',
                'publisher_id'
            ]);

            // Add new columns
            $table->uuid('secure_api_id')->nullable()->after('id');
            $table->unsignedBigInteger('adserver_id')->nullable()->after('secure_api_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            // Re-add the dropped columns
            $table->string('guid', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('company_address', 255)->nullable();
            $table->string('website_url', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->integer('publisher_id')->nullable();

            // Drop the new columns
            $table->dropColumn(['secure_api_id', 'adserver_id']);
        });
    }
};
