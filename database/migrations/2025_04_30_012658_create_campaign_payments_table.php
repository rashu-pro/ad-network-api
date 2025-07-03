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
        Schema::create('campaign_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('advertiser_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['eft', 'card', 'bank_transfer', 'manual', 'other'])->default('manual');
            $table->string('reference')->nullable(); // Transaction ID or internal ref
            $table->timestamp('payment_date')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_payments');
    }
};
