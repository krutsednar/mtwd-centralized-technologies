<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_name')->nullable();
            $table->string('organization_address')->nullable();
            $table->string('position_title')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
