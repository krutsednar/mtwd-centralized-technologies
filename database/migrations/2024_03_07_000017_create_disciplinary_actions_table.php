<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinaryActionsTable extends Migration
{
    public function up()
    {
        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date_released')->nullable();
            $table->string('admin_case_no')->unique();
            $table->longText('particulars')->nullable();
            $table->string('violation')->nullable();
            $table->string('penalties_meted')->nullable();
            $table->longText('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
