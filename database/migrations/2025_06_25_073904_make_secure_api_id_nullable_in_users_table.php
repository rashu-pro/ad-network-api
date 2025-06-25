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
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index before altering column
            $table->dropUnique('users_secure_api_id_unique');

            // Make the column nullable
            $table->unsignedBigInteger('secure_api_id')->nullable()->change();

            // Recreate the unique index
            $table->unique('secure_api_id', 'users_secure_api_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index
            $table->dropUnique('users_secure_api_id_unique');

            // Make column NOT NULL again
            $table->unsignedBigInteger('secure_api_id')->nullable(false)->change();

            // Restore unique index
            $table->unique('secure_api_id', 'users_secure_api_id_unique');
        });
    }
};
