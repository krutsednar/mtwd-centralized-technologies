<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_attendances', function (Blueprint $table) {
            // Mirror attendances table exactly
            $table->bigIncrements('id');
            $table->unsignedBigInteger('remote_id')->nullable()->unique();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('employee_number');
            $table->date('attendance_date');
            $table->time('morning_in')->nullable();
            $table->time('morning_out')->nullable();
            $table->time('afternoon_in')->nullable();
            $table->time('afternoon_out')->nullable();
            $table->string('ot_in')->nullable();
            $table->string('ot_out')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // v2-specific columns
            $table->float('match_score')->nullable();
            $table->float('liveness_score')->nullable();
            $table->float('quality_score')->nullable();
            $table->string('kiosk_id')->nullable();
            $table->string('verification_method')->default('face_v2');
            $table->float('top_match_margin')->nullable();

            $table->index('employee_number');
            $table->index('attendance_date');
            $table->unique(['employee_number', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_attendances');
    }
};
