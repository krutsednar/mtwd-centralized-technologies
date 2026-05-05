<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_inclusive_dates', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('duration'); // pending|used|cancelled|recalled
            $table->text('remarks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leave_inclusive_dates', function (Blueprint $table) {
            $table->dropColumn(['status', 'remarks']);
        });
    }
};
