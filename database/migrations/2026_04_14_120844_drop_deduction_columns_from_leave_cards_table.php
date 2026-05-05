<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->dropColumn(['deduction_vacation', 'deduction_sick', 'deduction_mandatory']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_cards', function (Blueprint $table) {
            $table->decimal('deduction_vacation', 10, 6)->default(0);
            $table->decimal('deduction_sick', 10, 6)->default(0);
            $table->decimal('deduction_mandatory', 10, 6)->default(0);
        });
    }
};
