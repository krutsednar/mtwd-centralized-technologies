<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-13: ListOrgStructure.php:31-40 loads the entire org tree
     * sorted by sort_order, then recurses by parent_id. Even more importantly,
     * the divisions.parent_id FK was created WITHOUT a supporting index, so
     * cascade lookups and reparent operations scan the table.
     *
     * Uses CREATE INDEX CONCURRENTLY; cannot run inside a transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS divisions_parent_id_sort_order_index '
            .'ON mct_proddb.divisions (parent_id, sort_order)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX CONCURRENTLY IF EXISTS mct_proddb.divisions_parent_id_sort_order_index'
        );
    }
};
