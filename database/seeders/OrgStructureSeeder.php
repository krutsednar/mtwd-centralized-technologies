<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class OrgStructureSeeder extends Seeder
{
    public function run(): void
    {
        // ── Set type for all existing rows ────────────────────────────────

        // Top-level offices (no parent)
        Division::whereIn('id', [1, 2])->update(['type' => 'ogm', 'sort_order' => 0]);

        // OAGM offices (report directly to OGM id=2)
        Division::whereIn('id', [3, 4])->update([
            'type' => 'oagm',
            'parent_id' => 2,
        ]);

        // Department-level (ODM)
        Division::where('id', 5)->update(['type' => 'odm', 'parent_id' => 3, 'sort_order' => 0]);
        Division::where('id', 6)->update(['type' => 'odm', 'parent_id' => 4, 'sort_order' => 0]);

        // Divisions that report directly to OGM (per known org structure)
        Division::whereIn('id', [12, 14, 16, 18])->update([
            'type' => 'division',
            'parent_id' => 2,
        ]);

        // Technical divisions under TOSD (id=5)
        Division::whereIn('id', [8, 10, 11, 13, 15])->update([
            'type' => 'division',
            'parent_id' => 5,
        ]);

        // Commercial/Customer divisions under AGMTOS (id=3)
        Division::whereIn('id', [7, 9])->update([
            'type' => 'division',
            'parent_id' => 3,
        ]);

        // Finance/HR/Admin divisions under HRAFD (id=6)
        Division::whereIn('id', [17, 19, 20, 21, 22])->update([
            'type' => 'division',
            'parent_id' => 6,
        ]);

        // ── Set sort_order for siblings ───────────────────────────────────
        $this->reorderSiblings(2);   // children of OGM
        $this->reorderSiblings(3);   // children of AGMTOS
        $this->reorderSiblings(4);   // children of AGMHRAF
        $this->reorderSiblings(5);   // children of TOSD
        $this->reorderSiblings(6);   // children of HRAFD
    }

    private function reorderSiblings(int $parentId): void
    {
        Division::where('parent_id', $parentId)
            ->orderBy('name')
            ->get()
            ->each(function (Division $div, int $i): void {
                $div->update(['sort_order' => $i]);
            });
    }
}
