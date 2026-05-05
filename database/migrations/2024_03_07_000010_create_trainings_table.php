<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingsTable extends Migration
{
    public function up()
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->integer('number_of_hours')->nullable();
            $table->string('conducted_by')->nullable();
            $table->string('ld_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
