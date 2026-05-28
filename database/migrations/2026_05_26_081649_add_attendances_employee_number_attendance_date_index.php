<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-8: DTR generation paths in routes/web.php (now controllers
     * after Task 1.8) filter attendances by (employee_number, attendance_date).
     * No composite index existed — only the auto-created profile_id FK index.
     *
     * Uses CREATE INDEX CONCURRENTLY to avoid table-level writes lock.
     * CONCURRENTLY cannot run inside a transaction, hence $withinTransaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS attendances_employee_number_attendance_date_index '
            .'ON mct_proddb.attendances (employee_number, attendance_date)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.attendances_employee_number_attendance_date_index'
        );
    }
};
