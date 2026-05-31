<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-14: LeaveApplicationResource:411-421 sums inclusive-date
     * durations per leave application: `$record->inclusiveDates()->sum('duration')`.
     * The existing single-col leave_application_id FK index can't efficiently
     * support range-of-dates queries that filter+group by leave_application_id.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS leave_inclusive_dates_leave_application_id_date_index '
            .'ON mct_proddb.leave_inclusive_dates (leave_application_id, date)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.leave_inclusive_dates_leave_application_id_date_index'
        );
    }
};
