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
        Schema::create('profile_trip_ticket', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->foreignId('trip_ticket_id')
            ->constrained()
            ->onDelete('cascade');

            $table->foreignId('profile_id')
            ->constrained()
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    // public function down(): void
    // {
    //     Schema::dropIfExists('profile_trip_ticket');
    // }
};
