<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire the legacy CompreFace v1 biometric enrollment system.
     *
     * Per audit Open Question Q2: the v1 system (BiometricEnrollment model,
     * Livewire\Hris\Biometrics, AutoAttendance, /biometrics/{phase} kiosk)
     * has been removed at the file level in the same change set. Face
     * Biometrics v2 (pgvector-backed face_profiles + face_embeddings) is
     * the active path. The biometric_enrollments table is no longer
     * written or read by any code.
     *
     * Data loss: 21 rows present at the time of this migration. These
     * were trial enrollments superseded by face_profiles. Image files in
     * storage/app/public are NOT deleted by this migration — those are
     * file-system cleanup, handled separately.
     *
     * down() recreates the schema for completeness, NOT the data.
     */
    public function up(): void
    {
        Schema::dropIfExists('biometric_enrollments');
    }

    /**
     * Recreate the schema that existed before this migration ran.
     * Mirrors the original 2026_05_14_100000_create_biometric_enrollments_table
     * migration. Does NOT restore row data — that requires a DB backup restore.
     */
    public function down(): void
    {
        Schema::create('biometric_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('image_1')->nullable();
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();
            $table->jsonb('compreface_face_ids')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });
    }
};
