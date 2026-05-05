<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('remote_id')->nullable()->unique()->after('id');
            $table->boolean('is_synced')->default(false)->after('ot_out');
            $table->timestamp('synced_at')->nullable()->after('is_synced');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['remote_id', 'is_synced', 'synced_at']);
        });
    }
};
