<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->date('from')->nullable()->after('days_applied_number');
            $table->date('to')->nullable()->after('from');
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn(['from', 'to']);
        });
    }
};
