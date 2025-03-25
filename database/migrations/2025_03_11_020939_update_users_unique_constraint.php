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
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);

            // Step 2: Add a composite unique constraint on 'email' and 'companyKey'
            $table->unique(['email', 'secure_api_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rollback: Remove composite unique constraint
            $table->dropUnique(['email', 'secure_api_id']);

            // Restore the unique constraint on 'email'
            $table->unique('email');
        });
    }
};
