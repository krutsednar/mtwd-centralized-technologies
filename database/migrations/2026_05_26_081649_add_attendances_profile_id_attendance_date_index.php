<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-9: DTR viewer queries Attendance by profile_id and
     * attendance_date together. The existing single-column profile_id FK
     * index can't seek by date — full subscan needed.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS attendances_profile_id_attendance_date_index '
            .'ON mct_proddb.attendances (profile_id, attendance_date)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.attendances_profile_id_attendance_date_index'
        );
    }
};
