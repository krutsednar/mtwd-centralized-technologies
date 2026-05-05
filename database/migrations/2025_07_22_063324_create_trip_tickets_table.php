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
        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->required()->unique();
            $table->date('date')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->string('profile_id')->nullable();
            $table->string('destination')->nullable();
            $table->string('purpose')->nullable();
            $table->datetime('office_departure')->nullable();
            $table->datetime('destination_arrival')->nullable();
            $table->datetime('destination_departure')->nullable();
            $table->datetime('office_arrival')->nullable();
            $table->decimal('distance_travelled', 15, 2)->nullable();
            $table->decimal('beginning_balance', 15, 2)->nullable();
            $table->decimal('purchase', 15, 2)->nullable();
            $table->decimal('consumed', 15, 2)->nullable();
            $table->decimal('ending_balance', 15, 2)->nullable();
            $table->string('oil_grease_lub_issued')->nullable();
            $table->decimal('speedometer_reading', 15, 2)->nullable();
            $table->decimal('actual_distance_travelled', 15, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
