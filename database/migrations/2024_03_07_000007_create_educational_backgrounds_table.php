<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalBackgroundsTable extends Migration
{
    public function up()
    {
        Schema::create('educational_backgrounds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('level');
            $table->string('school_name')->nullable();
            $table->string('degree_course')->nullable();
            $table->string('year_graduated')->nullable();
            $table->string('highest_grade')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->string('honors')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
