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
        Schema::create('school_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_course_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['school_course_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_lessons');
    }
};
