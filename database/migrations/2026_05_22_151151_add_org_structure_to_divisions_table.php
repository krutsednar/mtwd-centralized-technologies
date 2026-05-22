<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            // Self-referencing tree parent
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->foreign('parent_id')
                ->references('id')->on('divisions')
                ->nullOnDelete();

            // Org unit type — determines where in the hierarchy it sits
            $table->enum('type', ['ogm', 'oagm', 'odm', 'division'])
                ->default('division')
                ->after('abbreviation');

            // Sibling ordering for drag-and-drop persistence
            $table->integer('sort_order')->default(0)->after('type');

            // The permanently assigned head of this org unit
            $table->unsignedBigInteger('head_profile_id')->nullable()->after('sort_order');
            $table->foreign('head_profile_id')
                ->references('id')->on('profiles')
                ->nullOnDelete();

            // OIC toggle + assignment
            $table->boolean('oic_active')->default(false)->after('head_profile_id');
            $table->unsignedBigInteger('oic_profile_id')->nullable()->after('oic_active');
            $table->foreign('oic_profile_id')
                ->references('id')->on('profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['head_profile_id']);
            $table->dropForeign(['oic_profile_id']);
            $table->dropColumn([
                'parent_id', 'type', 'sort_order',
                'head_profile_id', 'oic_active', 'oic_profile_id',
            ]);
        });
    }
};
