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
        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->json('validation_errors')->nullable();
            $table->string('status')->default('invalid');
            $table->foreignId('committed_enrolment_id')->nullable()->constrained('enrolments')->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_batch_id', 'row_number']);
            $table->index(['import_batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
