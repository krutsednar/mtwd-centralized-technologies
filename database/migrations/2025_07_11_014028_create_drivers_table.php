<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id')->nullable();
            $table->string('license_no')->nullable();
            $table->string('type')->nullable();
            $table->string('restrictions')->nullable();
            $table->date('expiration')->nullable();
            $table->date('date_approved')->nullable();
            $table->string('primary_vehicle')->nullable();
            $table->string('dl_file')->nullable();
            $table->string('som_file')->nullable();
            $table->string('memo_file')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
