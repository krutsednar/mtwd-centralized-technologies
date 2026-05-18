<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PostgreSQL will not implicitly cast varchar → bigint in JOIN/WHERE conditions,
     * so any table whose division_id was created as string() must be altered.
     *
     * Safe because: every non-null value was verified to be numeric before running.
     * The USING clause handles the cast during the ALTER TABLE.
     */
    private array $tables = [
        'users',
        'profiles',
        'vehicles',
        'heavy_equipments',
        'equipment',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'division_id')) {
                continue;
            }

            // Null out any accidental empty strings first
            DB::statement("UPDATE \"{$table}\" SET division_id = NULL WHERE division_id = ''");

            // Drop any string DEFAULT before type change — PostgreSQL blocks the
            // ALTER if a DEFAULT value exists that cannot be auto-cast to bigint
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN division_id DROP DEFAULT");

            // Alter varchar → bigint using an explicit cast
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN division_id TYPE bigint USING division_id::bigint");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'division_id')) {
                continue;
            }

            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN division_id DROP DEFAULT");
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN division_id TYPE character varying(255) USING division_id::text");
        }
    }
};
