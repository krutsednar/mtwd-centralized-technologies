<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToOrganizationsTable extends Migration
{
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->foreign('profile_id', 'profile_fk_9573837')->references('id')->on('profiles');
        });
    }
}
