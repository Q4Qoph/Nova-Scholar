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
        Schema::create('guardian_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $table->string('relationship');
            $table->string('status')->default('active');
            $table->foreignId('verified_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['enrolment_id', 'guardian_user_id']);
            $table->index(['school_id', 'guardian_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_links');
    }
};
