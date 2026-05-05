<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEligibilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('eligibilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('eligibility')->nullable();
            $table->decimal('rating', 15, 2)->nullable();
            $table->date('date_of_examination')->nullable();
            $table->string('place_of_examination')->nullable();
            $table->string('license_no')->nullable();
            $table->date('date_issued')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
