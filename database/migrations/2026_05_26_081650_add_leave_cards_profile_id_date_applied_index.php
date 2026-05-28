<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-10: LeaveCardResource table sorts by date_applied and
     * filters by profile_id. The existing profile_id FK index cannot
     * efficiently support the order-by-date predicate.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS leave_cards_profile_id_date_applied_index '
            .'ON mct_proddb.leave_cards (profile_id, date_applied)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.leave_cards_profile_id_date_applied_index'
        );
    }
};
