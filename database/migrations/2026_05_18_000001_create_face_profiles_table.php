<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('profile_id')->unique()->constrained('profiles')->cascadeOnDelete();
            $table->boolean('is_enrolled')->default(false);
            $table->timestamp('enrolled_at')->nullable();
            $table->float('enrollment_quality_score')->nullable();
            $table->smallInteger('template_count')->default(0);
            $table->string('enrollment_source')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->float('last_match_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_profiles');
    }
};
