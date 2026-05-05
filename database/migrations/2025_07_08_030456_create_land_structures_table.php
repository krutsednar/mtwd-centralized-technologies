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
        Schema::create('land_structures', function (Blueprint $table) {
            $table->string('land_structure_type_id')->nullable();
            $table->string('property_name')->nullable();
            $table->string('lot_area')->nullable();
            $table->date('date_acquired')->nullable();
            $table->date('date_established')->nullable();
            $table->string('address')->nullable();
            $table->string('title_no')->nullable()->unique();
            $table->string('title_file')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('land_structures');
    }
};
