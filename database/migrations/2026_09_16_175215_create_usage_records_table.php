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
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_reservation_id')->constrained()->restrictOnDelete()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_period_id')->constrained()->restrictOnDelete();
            $table->string('feature_code');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('estimated_cost_minor')->nullable();
            $table->unsignedBigInteger('actual_cost_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'feature_code', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
