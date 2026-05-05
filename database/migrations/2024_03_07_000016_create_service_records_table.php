<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('from');
            $table->date('to')->nullable();
            $table->string('position')->nullable();
            $table->string('sg')->nullable();
            $table->string('increment')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->decimal('allowance', 15, 2)->nullable();
            $table->string('remarks');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
