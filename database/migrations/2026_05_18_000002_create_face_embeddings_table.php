<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_embeddings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('face_profile_id')->constrained('face_profiles')->cascadeOnDelete();
            $table->smallInteger('slot');
            $table->float('quality_score');
            $table->string('source');
            $table->string('source_path')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
            $table->unique(['face_profile_id', 'slot']);
        });

        // Temporarily include public in search_path so vector type is visible
        DB::statement('SET search_path TO mct_devdb, public');
        DB::statement('ALTER TABLE mct_devdb.face_embeddings ADD COLUMN embedding vector(512)');
        DB::statement('CREATE INDEX face_embeddings_hnsw ON mct_devdb.face_embeddings USING hnsw (embedding vector_cosine_ops)');
        DB::statement('SET search_path TO mct_devdb');
    }

    public function down(): void
    {
        Schema::dropIfExists('face_embeddings');
    }
};
