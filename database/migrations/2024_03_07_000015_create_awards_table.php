<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAwardsTable extends Migration
{
    public function up()
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date_received')->nullable();
            $table->longText('particulars')->nullable();
            $table->string('awards_received')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
