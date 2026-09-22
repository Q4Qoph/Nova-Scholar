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
        Schema::create('learner_class_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['school_id', 'status', 'starts_on']);
            $table->index(['enrolment_id', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_class_memberships');
    }
};
