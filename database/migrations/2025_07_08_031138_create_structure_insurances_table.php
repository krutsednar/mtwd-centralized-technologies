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
        Schema::create('structure_insurances', function (Blueprint $table) {
             $table->id();
            $table->string('policy_no')->nullable();
            $table->date('date_issued')->nullable();
            $table->date('expiration')->nullable();
            $table->string('attachment')->nullable();
            $table->string('land_structure_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structure_insurances');
    }
};
