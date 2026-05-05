<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkExperiencesTable extends Migration
{
    public function up()
    {
        Schema::create('work_experiences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->string('position_title')->nullable();
            $table->string('agency')->nullable();
            $table->decimal('monthly_salary', 15, 2)->nullable();
            $table->string('salary_grade')->nullable();
            $table->string('appointment_status')->nullable();
            $table->boolean('government')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
