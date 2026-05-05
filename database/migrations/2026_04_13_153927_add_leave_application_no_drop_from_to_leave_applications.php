<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('leave_application_no')->nullable()->unique()->after('id');
            $table->dropColumn(['from', 'to']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn('leave_application_no');
            $table->date('from')->nullable();
            $table->date('to')->nullable();
        });
    }
};
