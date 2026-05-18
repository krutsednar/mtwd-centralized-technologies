<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->foreign('profile_id')->references('id')->on('profiles')->nullOnDelete();
            $table->string('event');
            $table->float('match_score')->nullable();
            $table->float('liveness_score')->nullable();
            $table->float('quality_score')->nullable();
            $table->string('reason')->nullable();
            $table->string('photo_hash', 64)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('kiosk_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_audit_log');
    }
};
