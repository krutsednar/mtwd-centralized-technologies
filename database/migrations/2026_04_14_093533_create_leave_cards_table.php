<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();

            $table->date('date_applied');
            $table->string('ref_code')->nullable();
            $table->string('category');
            $table->string('period_covered')->nullable(); // stored as free-form string (dd-hh-mm or date range)

            $table->decimal('duration', 10, 6)->default(0);

            // Vacation Leave columns
            $table->decimal('vl_earned', 10, 6)->default(0);
            $table->decimal('vl_with_pay', 10, 6)->default(0);
            $table->decimal('vl_without_pay', 10, 6)->default(0);

            // Sick Leave columns
            $table->decimal('sl_earned', 10, 6)->default(0);
            $table->decimal('sl_with_pay', 10, 6)->default(0);
            $table->decimal('sl_without_pay', 10, 6)->default(0);

            // Deductions
            $table->decimal('deduction_vacation', 10, 6)->default(0);
            $table->decimal('deduction_sick', 10, 6)->default(0);
            $table->decimal('deduction_mandatory', 10, 6)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_cards');
    }
};
