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
        Schema::create('enrolments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_profile_id')->constrained()->restrictOnDelete();
            $table->string('admission_number');
            $table->string('status')->default('active');
            $table->date('enrolled_at');
            $table->date('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'admission_number']);
            $table->index(['school_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrolments');
    }
};
