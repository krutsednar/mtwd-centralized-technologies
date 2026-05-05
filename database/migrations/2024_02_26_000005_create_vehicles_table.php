<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vehicle_type_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->unique();
            $table->string('certificate_of_registration')->nullable();
            $table->string('cr_file')->nullable();
            $table->string('chasis_no')->nullable();
            $table->string('chasis_file')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('plate_no')->nullable();
            $table->date('date_acquired')->nullable();
            $table->string('par_no')->nullable();
            $table->string('custodian')->nullable();
            $table->string('division_id')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->longText('description')->nullable();
            $table->longText('remarks')->nullable();
            $table->longText('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
