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
        Schema::create('school_lesson_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_lesson_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('display_name', 255);
            $table->string('storage_disk', 64)->default('local');
            $table->string('storage_key', 512)->nullable();
            $table->string('media_type', 128);
            $table->unsignedBigInteger('byte_size');
            $table->char('sha256', 64);
            $table->string('status', 32)->default('quarantined');
            $table->string('rights_basis', 32);
            $table->string('rights_reference', 512)->nullable();
            $table->timestamp('rights_attested_at');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanner_name', 64)->nullable();
            $table->string('scanner_signature_version', 128)->nullable();
            $table->string('scan_result_code', 64)->nullable();
            $table->timestamp('purge_after')->nullable();
            $table->timestamp('bytes_purged_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'bytes_purged_at']);
            $table->index(['school_lesson_version_id', 'status']);
            $table->index(['status', 'purge_after']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_lesson_resources');
    }
};
