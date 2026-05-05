<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildrenTable extends Migration
{
    public function up()
    {
        Schema::create('children', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('date_of_birth');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
