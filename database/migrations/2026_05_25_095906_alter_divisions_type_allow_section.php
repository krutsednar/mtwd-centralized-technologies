<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit finding M-1: divisions.type CHECK constraint excluded 'section'
     * even though Division::TYPE_SECTION was added to the model. Any insert
     * with type='section' would fail with constraint violation.
     *
     * Per Open Question Q5: convert to varchar+CHECK. The column is already
     * varchar(255); only the CHECK constraint (divisions_type_check) needs
     * to be widened to include 'section'.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE divisions DROP CONSTRAINT IF EXISTS divisions_type_check');

        DB::statement(
            'ALTER TABLE divisions ADD CONSTRAINT divisions_type_check '
            .'CHECK (type::text = ANY (ARRAY['
            ."'ogm'::varchar, "
            ."'oagm'::varchar, "
            ."'odm'::varchar, "
            ."'division'::varchar, "
            ."'section'::varchar"
            .']::text[]))'
        );
    }

    /**
     * Restore the original 4-value CHECK. Refuses to run if any rows use
     * 'section' because the resulting constraint would immediately fail
     * validation on existing data — operator must reclassify or remove
     * those rows first.
     */
    public function down(): void
    {
        $sectionCount = (int) DB::scalar("SELECT COUNT(*) FROM divisions WHERE type = 'section'");

        if ($sectionCount > 0) {
            throw new \RuntimeException(
                "Cannot rollback: {$sectionCount} divisions row(s) have type='section'. "
                .'Reclassify or delete those rows before rolling back.'
            );
        }

        DB::statement('ALTER TABLE divisions DROP CONSTRAINT IF EXISTS divisions_type_check');

        DB::statement(
            'ALTER TABLE divisions ADD CONSTRAINT divisions_type_check '
            .'CHECK (type::text = ANY (ARRAY['
            ."'ogm'::varchar, "
            ."'oagm'::varchar, "
            ."'odm'::varchar, "
            ."'division'::varchar"
            .']::text[]))'
        );
    }
};
