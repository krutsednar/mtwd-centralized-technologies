<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->string('duration')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->decimal('duration', 10, 6)->default(0)->change();
        });
    }
};
