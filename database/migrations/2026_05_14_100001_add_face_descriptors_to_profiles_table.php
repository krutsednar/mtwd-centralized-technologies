<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->jsonb('face_descriptors')->nullable()->after('face_enrolled');
            // Stores an array of descriptor arrays:
            // [[0.12, -0.34, ...128 floats], [...], [...]]
            // Keep 3-5 descriptors per employee for better accuracy.
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('face_descriptors');
        });
    }
};
