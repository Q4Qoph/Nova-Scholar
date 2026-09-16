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
        Schema::create('usage_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_period_id')->constrained()->cascadeOnDelete();
            $table->string('feature_code');
            $table->string('request_key')->unique();
            $table->unsignedInteger('quantity');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_period_id', 'feature_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_reservations');
    }
};
