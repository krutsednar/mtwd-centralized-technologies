<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_inclusive_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('duration', 3, 1)->default(1.0); // 1 = whole day, 0.5 = half day
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_inclusive_dates');
    }
};
