<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Run the migrations.
         */

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('asset_type_id')->nullable()->after('id');

            $table->foreign('asset_type_id')
                ->references('id')
                ->on('asset_types')
                ->onDelete('set null');

            // drop the old column
            if (Schema::hasColumn('assets', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_type_id']);
            $table->dropColumn('asset_type_id');
        });
    }
};
