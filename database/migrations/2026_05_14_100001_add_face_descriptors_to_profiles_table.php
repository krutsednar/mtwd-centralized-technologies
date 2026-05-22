<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('profiles', 'face_descriptors')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->jsonb('face_descriptors')->nullable()->after('face_enrolled');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('face_descriptors');
        });
    }
};
