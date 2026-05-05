<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToSkillsTable extends Migration
{
    public function up()
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->foreign('profile_id', 'profile_fk_9573838')->references('id')->on('profiles');
        });
    }
}
