<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // Documentary requirements per CS Form No. 6 (page 2). Array of stored
            // file paths (public disk).
            $table->jsonb('supporting_documents')->nullable()->after('commutation');
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn('supporting_documents');
        });
    }
};
