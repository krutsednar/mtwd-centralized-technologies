<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-12: face_attendances has indexes on employee_number,
     * attendance_date, and the composite (employee_number, attendance_date)
     * UNIQUE — but NOT profile_id. Per-profile attendance lookups
     * (the kiosk's resolveNextPhaseField) full-scan the table.
     *
     * profile_id column confirmed present via information_schema query
     * before adding this index.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS face_attendances_profile_id_attendance_date_index '
            .'ON mct_proddb.face_attendances (profile_id, attendance_date)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.face_attendances_profile_id_attendance_date_index'
        );
    }
};
