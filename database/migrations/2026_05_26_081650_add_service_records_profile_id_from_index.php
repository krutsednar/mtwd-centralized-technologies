<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-11: LeaveApplicationResource:87-92 looks up the latest
     * ServiceRecord per profile via `where('profile_id', $id)->orderByDesc('from')`.
     * The existing single-col profile_id index requires a sort step.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS service_records_profile_id_from_index '
            .'ON mct_proddb.service_records (profile_id, "from")'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.service_records_profile_id_from_index'
        );
    }
};
