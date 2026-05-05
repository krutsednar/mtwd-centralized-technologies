<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToChildrenTable extends Migration
{
    public function up()
    {
        Schema::table('children', function (Blueprint $table) {
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->foreign('profile_id', 'profile_fk_9573832')->references('id')->on('profiles');
        });
    }
}
