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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_type_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->unique();
            $table->date('date_acquired')->nullable();
            $table->string('par_no')->nullable();
            $table->string('custodian')->nullable();
            $table->string('division_id')->nullable();
            $table->string('location')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->longText('desc')->nullable();
            $table->longText('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
