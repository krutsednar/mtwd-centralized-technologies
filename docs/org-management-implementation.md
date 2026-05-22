# Organization Management — Full Implementation Specification

## Codebase Context (Read Before Coding)

- **Framework:** Laravel 12, Filament v3, Livewire v3
- **Panel:** `app/Filament/Hris/` — the HRIS panel  
- **Existing model:** `app/Models/Division.php` — already has `name`, `abbreviation`, `SoftDeletes`, `LogsActivity`
- **Existing table:** `divisions` — columns: `id`, `name`, `abbreviation`, `created_at`, `updated_at`, `deleted_at`
- **22 existing division rows** — all profiles link to them via `profiles.division_id`; do NOT break this FK
- **Profile model:** `app/Models/Profile.php` — has `division_id` FK; has `full_name` accessor and `employee_number`
- **Code style:** Follow pint formatting. Run `vendor/bin/pint --dirty` before finishing. Use PHP 8.3 constructor promotion where applicable. Always explicit return types.
- **DB schema:** PostgreSQL, schema `mct_proddb`

### Existing division data to preserve (22 rows)

| ID | Name | Abbr | Inferred type |
|----|------|------|---------------|
| 1  | Office of the Board of Directors | OBD | ogm |
| 2  | Office of the General Manager | OGM | ogm |
| 3  | Office of the AGM for Technical Services and Operations | AGMTOS | oagm |
| 4  | Office of the AGM for Finance and Administration | AGMHRAF | oagm |
| 5  | Technical Services and Operations Department | TOSD | odm |
| 6  | Finance and Administration Department | HRAFD | odm |
| 7–22 | Various divisions | — | division |

The seeder will set `type` and an initial `parent_id` for obvious nodes. HR uses drag-and-drop to finalise the rest.

---

## Step 1 — Install Package

```bash
composer require staudenmeir/laravel-adjacency-list
```

Confirm it appears in `composer.json` under `require`.

---

## Step 2 — Migration

Run:
```bash
php artisan make:migration add_org_structure_to_divisions_table --no-interaction
```

Fill the generated file exactly as follows:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            // Self-referencing tree parent
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->foreign('parent_id')
                  ->references('id')->on('divisions')
                  ->nullOnDelete();

            // Org unit type — determines where in the hierarchy it sits
            $table->enum('type', ['ogm', 'oagm', 'odm', 'division'])
                  ->default('division')
                  ->after('abbreviation');

            // Sibling ordering for drag-and-drop persistence
            $table->integer('sort_order')->default(0)->after('type');

            // The permanently assigned head of this org unit
            $table->unsignedBigInteger('head_profile_id')->nullable()->after('sort_order');
            $table->foreign('head_profile_id')
                  ->references('id')->on('profiles')
                  ->nullOnDelete();

            // OIC toggle + assignment
            $table->boolean('oic_active')->default(false)->after('head_profile_id');
            $table->unsignedBigInteger('oic_profile_id')->nullable()->after('oic_active');
            $table->foreign('oic_profile_id')
                  ->references('id')->on('profiles')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['head_profile_id']);
            $table->dropForeign(['oic_profile_id']);
            $table->dropColumn([
                'parent_id', 'type', 'sort_order',
                'head_profile_id', 'oic_active', 'oic_profile_id',
            ]);
        });
    }
};
```

Run: `php artisan migrate --no-interaction`

---

## Step 3 — Seeder

Create:
```bash
php artisan make:seeder OrgStructureSeeder --no-interaction
```

File: `database/seeders/OrgStructureSeeder.php`

```php
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
            'type'      => 'oagm',
            'parent_id' => 2,
        ]);

        // Department-level (ODM)
        Division::where('id', 5)->update(['type' => 'odm', 'parent_id' => 3, 'sort_order' => 0]);
        Division::where('id', 6)->update(['type' => 'odm', 'parent_id' => 4, 'sort_order' => 0]);

        // Divisions that report directly to OGM (per known org structure)
        Division::whereIn('id', [12, 14, 16, 18])->update([
            'type'      => 'division',
            'parent_id' => 2,
        ]);

        // Technical divisions under TOSD (id=5)
        Division::whereIn('id', [8, 10, 11, 13, 15])->update([
            'type'      => 'division',
            'parent_id' => 5,
        ]);

        // Commercial/Customer divisions under AGMTOS (id=3)
        Division::whereIn('id', [7, 9])->update([
            'type'      => 'division',
            'parent_id' => 3,
        ]);

        // Finance/HR/Admin divisions under HRAFD (id=6)
        Division::whereIn('id', [17, 19, 20, 21, 22])->update([
            'type'      => 'division',
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
```

Run: `php artisan db:seed --class=OrgStructureSeeder --no-interaction`

---

## Step 4 — Update Division Model

Replace `app/Models/Division.php` entirely:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Division extends Model
{
    use HasRecursiveRelationships;
    use LogsActivity;
    use SoftDeletes;

    // ── Types ────────────────────────────────────────────────────────────────

    public const TYPE_OGM      = 'ogm';
    public const TYPE_OAGM     = 'oagm';
    public const TYPE_ODM      = 'odm';
    public const TYPE_DIVISION = 'division';

    public const TYPE_LABELS = [
        self::TYPE_OGM      => 'Office of the General Manager',
        self::TYPE_OAGM     => 'Office of the AGM',
        self::TYPE_ODM      => 'Office of the Department Manager',
        self::TYPE_DIVISION => 'Division',
    ];

    /**
     * Types that a given type may be placed under (parent type constraints).
     * Empty array = must be root (no parent allowed).
     */
    public const ALLOWED_PARENT_TYPES = [
        self::TYPE_OGM      => [],
        self::TYPE_OAGM     => [self::TYPE_OGM],
        self::TYPE_ODM      => [self::TYPE_OGM, self::TYPE_OAGM],
        self::TYPE_DIVISION => [self::TYPE_OGM, self::TYPE_OAGM, self::TYPE_ODM],
    ];

    // ── Eloquent config ──────────────────────────────────────────────────────

    protected $fillable = [
        'parent_id',
        'name',
        'abbreviation',
        'type',
        'sort_order',
        'head_profile_id',
        'oic_active',
        'oic_profile_id',
    ];

    protected function casts(): array
    {
        return [
            'oic_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /** The permanently assigned head of this org unit. */
    public function head(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'head_profile_id');
    }

    /** The Officer-In-Charge (active only when oic_active = true). */
    public function oic(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'oic_profile_id');
    }

    /** Profiles assigned to this org unit. */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'division_id');
    }

    // ── Signatory helpers ────────────────────────────────────────────────────

    /**
     * Returns the active signatory for this org unit.
     * When oic_active is true and an OIC is assigned, the OIC signs.
     * Otherwise the head signs.
     */
    public function getActiveSignatory(): ?Profile
    {
        if ($this->oic_active && $this->oic_profile_id !== null) {
            return $this->oic;
        }

        return $this->head;
    }

    /**
     * Returns the ordered signature chain from this unit up to the root.
     * Each entry is the active signatory (OIC or head) of that level.
     * Nulls are filtered — units with no signatory are skipped.
     *
     * Usage: $profile->division->signatureChain()
     *
     * @return Collection<int, Profile>
     */
    public function signatureChain(): Collection
    {
        return $this->ancestorsAndSelf()
            ->with(['head', 'oic'])
            ->orderByDepth('desc')   // deepest (this unit) first, root last
            ->get()
            ->map(fn (self $unit): ?Profile => $unit->getActiveSignatory())
            ->filter()
            ->values();
    }

    // ── Drag-and-drop validation ─────────────────────────────────────────────

    /**
     * Whether this node can be placed under the given parent type.
     */
    public function canBeChildOf(?string $parentType): bool
    {
        $allowed = self::ALLOWED_PARENT_TYPES[$this->type] ?? [];

        if ($parentType === null) {
            return empty($allowed);   // root placement allowed only for OGM
        }

        return in_array($parentType, $allowed, true);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Root nodes only (no parent). */
    public function scopeRoots(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('parent_id')->orderBy('sort_order');
    }

    // ── Activity log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
```

**Important:** The `HasRecursiveRelationships` trait uses `parent_id` by default as the parent key — no extra configuration needed.

---

## Step 5 — Create StructureManagementResource

### 5a. Resource class

Run:
```bash
php artisan make:filament-resource StructureManagement --panel=hris --no-interaction
```

Replace `app/Filament/Hris/Resources/StructureManagementResource.php` entirely:

```php
<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\StructureManagementResource\Pages;
use App\Models\Division;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StructureManagementResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static ?string $slug = 'org-structure';

    protected static ?string $navigationGroup = 'Organization Management';

    protected static ?string $navigationLabel = 'Structure Management';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'Org Unit';

    protected static ?string $pluralModelLabel = 'Org Structure';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;   // creation handled from within the tree page
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrgStructure::route('/'),
        ];
    }
}
```

### 5b. Custom list page

Create file `app/Filament/Hris/Resources/StructureManagementResource/Pages/ListOrgStructure.php`:

```php
<?php

namespace App\Filament\Hris\Resources\StructureManagementResource\Pages;

use App\Filament\Hris\Resources\StructureManagementResource;
use App\Models\Division;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class ListOrgStructure extends Page
{
    protected static string $resource = StructureManagementResource::class;

    protected static string $view = 'filament.hris.pages.org-structure';

    /** Full tree loaded once; re-loaded after any mutation. */
    public array $tree = [];

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadTree();
    }

    // ── Tree loading ─────────────────────────────────────────────────────────

    public function loadTree(): void
    {
        $nodes = Division::withTrashed(false)
            ->with(['head', 'oic'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->tree = $this->buildTreeArray($nodes, null);
    }

    /**
     * Converts a flat collection to a nested array structure Blade can recurse.
     *
     * @param  Collection<int, Division>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function buildTreeArray(Collection $nodes, ?int $parentId): array
    {
        return $nodes
            ->filter(fn (Division $n) => $n->parent_id === $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(fn (Division $n) => [
                'id'           => $n->id,
                'name'         => $n->name,
                'abbreviation' => $n->abbreviation,
                'type'         => $n->type,
                'type_label'   => Division::TYPE_LABELS[$n->type] ?? $n->type,
                'head_name'    => $n->head?->full_name,
                'oic_active'   => $n->oic_active,
                'oic_name'     => $n->oic?->full_name,
                'children'     => $this->buildTreeArray($nodes, $n->id),
            ])
            ->toArray();
    }

    // ── Drag-and-drop actions (called from JS via $wire) ─────────────────────

    /**
     * Reorder siblings after a drag within the same parent.
     *
     * @param  array<int>  $orderedIds  IDs in new order
     * @param  int|null    $parentId    The common parent (null = root)
     */
    public function reorder(array $orderedIds, ?int $parentId): void
    {
        foreach ($orderedIds as $index => $id) {
            Division::where('id', $id)->update([
                'sort_order' => $index,
                'parent_id'  => $parentId,
            ]);
        }

        $this->loadTree();
    }

    /**
     * Move a node to a different parent (reparent).
     * Validates type constraints before persisting.
     */
    public function reparent(int $nodeId, ?int $newParentId, int $newIndex): void
    {
        $node = Division::find($nodeId);

        if (! $node) {
            return;
        }

        $newParentType = $newParentId
            ? Division::find($newParentId)?->type
            : null;

        if (! $node->canBeChildOf($newParentType)) {
            Notification::make()
                ->title('Invalid placement')
                ->body("A {$node->type} cannot be placed under a {$newParentType}.")
                ->warning()
                ->send();

            $this->loadTree();   // revert the UI

            return;
        }

        // Reorder existing siblings to make room at $newIndex
        $siblings = Division::where('parent_id', $newParentId)
            ->where('id', '!=', $nodeId)
            ->orderBy('sort_order')
            ->get();

        foreach ($siblings as $i => $sibling) {
            $order = $i >= $newIndex ? $i + 1 : $i;
            $sibling->update(['sort_order' => $order]);
        }

        $node->update([
            'parent_id'  => $newParentId,
            'sort_order' => $newIndex,
        ]);

        $this->loadTree();
    }

    // ── Add new org unit action ───────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_unit')
                ->label('Add Org Unit')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('abbreviation')
                        ->label('Abbreviation')
                        ->required()
                        ->maxLength(30),

                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(Division::TYPE_LABELS)
                        ->required(),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Org Unit')
                        ->options(
                            Division::orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->action(function (array $data): void {
                    $node = Division::create([
                        'name'         => $data['name'],
                        'abbreviation' => $data['abbreviation'],
                        'type'         => $data['type'],
                        'parent_id'    => $data['parent_id'] ?? null,
                        'sort_order'   => 999,
                    ]);

                    // Validate placement
                    $parentType = $node->parent?->type;

                    if (! $node->canBeChildOf($parentType)) {
                        $node->delete();

                        Notification::make()
                            ->title('Invalid parent')
                            ->body("A {$node->type} cannot be placed under a {$parentType}.")
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->loadTree();

                    Notification::make()
                        ->title('Org unit added')
                        ->success()
                        ->send();
                }),

            Action::make('edit_unit')
                ->label('Edit Unit')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('id')
                        ->label('Select Org Unit')
                        ->options(Division::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if (! $state) {
                                return;
                            }

                            $unit = Division::find($state);

                            if (! $unit) {
                                return;
                            }

                            $set('name', $unit->name);
                            $set('abbreviation', $unit->abbreviation);
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('abbreviation')
                        ->label('Abbreviation')
                        ->required()
                        ->maxLength(30),
                ])
                ->columns(2)
                ->action(function (array $data): void {
                    Division::where('id', $data['id'])->update([
                        'name'         => $data['name'],
                        'abbreviation' => $data['abbreviation'],
                    ]);

                    $this->loadTree();

                    Notification::make()
                        ->title('Org unit updated')
                        ->success()
                        ->send();
                }),
        ];
    }
}
```

### 5c. Blade view for the tree

Create file `resources/views/filament/hris/pages/org-structure.blade.php`:

```blade
<x-filament-panels::page>
    {{-- Load SortableJS from CDN --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endpush

    <style>
        .org-tree ul { list-style: none; padding-left: 1.5rem; margin: 0; }
        .org-tree > ul { padding-left: 0; }
        .org-node { margin: 4px 0; }
        .org-node-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: grab;
            user-select: none;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
            transition: box-shadow .15s;
        }
        .org-node-card:hover  { box-shadow: 0 3px 8px rgba(0,0,0,.1); }
        .org-node-card.sortable-ghost { opacity: .4; }
        .drag-handle { color: #9ca3af; cursor: grab; flex-shrink: 0; }
        .type-badge {
            font-size: 10px; font-weight: 600;
            padding: 2px 7px; border-radius: 999px;
            white-space: nowrap; flex-shrink: 0;
        }
        .type-ogm      { background:#dbeafe; color:#1d4ed8; }
        .type-oagm     { background:#ede9fe; color:#6d28d9; }
        .type-odm      { background:#fef3c7; color:#92400e; }
        .type-division { background:#dcfce7; color:#15803d; }
        .org-node-name { font-size: 13px; font-weight: 500; flex: 1; }
        .org-node-abbr { font-size: 11px; color: #6b7280; }
        .org-node-head { font-size: 11px; color: #374151; margin-left: auto; white-space: nowrap; }
        .org-node-oic  { font-size: 11px; color: #b45309; font-style: italic; white-space: nowrap; }
        .children-container { min-height: 4px; }
        .sortable-chosen { box-shadow: 0 4px 12px rgba(0,0,0,.15) !important; }
    </style>

    <div class="org-tree" x-data="orgTree($wire)">
        <ul
            class="children-container"
            data-parent-id="null"
            data-parent-type="root"
            x-ref="root"
        >
            @foreach ($tree as $node)
                @include('filament.hris.partials.org-tree-node', ['node' => $node, 'depth' => 0])
            @endforeach
        </ul>
    </div>

    <script>
    function orgTree(wire) {
        return {
            sortables: [],

            init() {
                this.$nextTick(() => this.initSortable());

                // Re-init after every Livewire re-render
                Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    succeed(({ snapshot, effect }) => {
                        this.$nextTick(() => this.initSortable());
                    });
                });
            },

            initSortable() {
                this.sortables.forEach(s => s.destroy());
                this.sortables = [];

                const containers = document.querySelectorAll('.children-container');

                containers.forEach(container => {
                    const s = Sortable.create(container, {
                        group: {
                            name: 'org',
                            put: (to, from, dragEl) => {
                                const nodeType   = dragEl.dataset.type;
                                const parentType = to.el.dataset.parentType;
                                return this.isValidDrop(nodeType, parentType);
                            },
                        },
                        handle:    '.drag-handle',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',

                        onEnd: (evt) => {
                            const nodeId = parseInt(evt.item.dataset.id);

                            const rawParent = evt.to.dataset.parentId;
                            const newParentId = (rawParent === 'null' || rawParent === null)
                                ? null
                                : parseInt(rawParent);

                            const newIndex = evt.newIndex;

                            // If parent didn't change, just reorder siblings
                            const oldParentId = evt.from.dataset.parentId;

                            if (evt.from === evt.to) {
                                const ids = [...evt.to.querySelectorAll(':scope > .org-node')]
                                    .map(li => parseInt(li.dataset.id));
                                wire.reorder(ids, newParentId);
                            } else {
                                wire.reparent(nodeId, newParentId, newIndex);
                            }
                        },
                    });

                    this.sortables.push(s);
                });
            },

            isValidDrop(nodeType, parentType) {
                const rules = {
                    ogm:      [],
                    oagm:     ['ogm'],
                    odm:      ['ogm', 'oagm'],
                    division: ['ogm', 'oagm', 'odm'],
                };

                if (parentType === 'root' || parentType === null) {
                    return (rules[nodeType] ?? []).length === 0;
                }

                return (rules[nodeType] ?? []).includes(parentType);
            },
        };
    }
    </script>
</x-filament-panels::page>
```

### 5d. Recursive partial

Create file `resources/views/filament/hris/partials/org-tree-node.blade.php`:

```blade
<li
    class="org-node"
    data-id="{{ $node['id'] }}"
    data-type="{{ $node['type'] }}"
    wire:key="org-node-{{ $node['id'] }}"
>
    <div class="org-node-card">
        <span class="drag-handle">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm8-16a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
            </svg>
        </span>

        <span class="type-badge type-{{ $node['type'] }}">
            {{ $node['type_label'] }}
        </span>

        <span class="org-node-name">{{ $node['name'] }}</span>
        <span class="org-node-abbr">({{ $node['abbreviation'] }})</span>

        @if ($node['oic_active'] && $node['oic_name'])
            <span class="org-node-oic">OIC: {{ $node['oic_name'] }}</span>
        @elseif ($node['head_name'])
            <span class="org-node-head">{{ $node['head_name'] }}</span>
        @endif
    </div>

    @if (!empty($node['children']))
        <ul
            class="children-container"
            data-parent-id="{{ $node['id'] }}"
            data-parent-type="{{ $node['type'] }}"
        >
            @foreach ($node['children'] as $child)
                @include('filament.hris.partials.org-tree-node', [
                    'node'  => $child,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </ul>
    @else
        {{-- Empty container still needed for drag targets --}}
        <ul
            class="children-container"
            data-parent-id="{{ $node['id'] }}"
            data-parent-type="{{ $node['type'] }}"
        ></ul>
    @endif
</li>
```

---

## Step 6 — Create SupervisorManagementResource

Run:
```bash
php artisan make:filament-resource SupervisorManagement --panel=hris --no-interaction
```

Replace `app/Filament/Hris/Resources/SupervisorManagementResource.php` entirely:

```php
<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\SupervisorManagementResource\Pages;
use App\Models\Division;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupervisorManagementResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static ?string $slug = 'supervisor-management';

    protected static ?string $navigationGroup = 'Organization Management';

    protected static ?string $navigationLabel = 'Supervisor Management';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Supervisor Assignment';

    protected static ?string $pluralModelLabel = 'Supervisor Management';

    protected static ?int $navigationSort = 31;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    private static function profileOptions(): array
    {
        return Profile::query()
            ->orderBy('surname')
            ->get()
            ->mapWithKeys(fn (Profile $p) => [
                $p->id => $p->employee_number.' — '.$p->full_name,
            ])
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Division::query()->orderBy('sort_order')->orderBy('name'))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Division::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Division::TYPE_OGM      => 'primary',
                        Division::TYPE_OAGM     => 'warning',
                        Division::TYPE_ODM      => 'info',
                        Division::TYPE_DIVISION => 'success',
                        default                 => 'gray',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Org Unit')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Division $record): string => $record->abbreviation ?? ''),

                Tables\Columns\TextColumn::make('head.full_name')
                    ->label('Head')
                    ->placeholder('— not assigned —')
                    ->searchable(['profiles.surname', 'profiles.first_name']),

                Tables\Columns\IconColumn::make('oic_active')
                    ->label('OIC Active')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('oic.full_name')
                    ->label('OIC')
                    ->placeholder('—')
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Level')
                    ->options(Division::TYPE_LABELS),

                Tables\Filters\TernaryFilter::make('oic_active')
                    ->label('OIC Active'),

                Tables\Filters\Filter::make('no_head')
                    ->label('No Head Assigned')
                    ->query(fn ($query) => $query->whereNull('head_profile_id')),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-pencil')
                    ->form(function (Division $record): array {
                        $options = static::profileOptions();

                        return [
                            Forms\Components\Select::make('head_profile_id')
                                ->label('Head of Group')
                                ->options($options)
                                ->searchable()
                                ->nullable()
                                ->default($record->head_profile_id)
                                ->columnSpanFull()
                                ->helperText('The permanently assigned head of this org unit.'),

                            Forms\Components\Toggle::make('oic_active')
                                ->label('Activate OIC')
                                ->default($record->oic_active)
                                ->live()
                                ->helperText('When ON, the OIC signs instead of the head. Turn OFF when the head returns.'),

                            Forms\Components\Select::make('oic_profile_id')
                                ->label('Officer-In-Charge (OIC)')
                                ->options($options)
                                ->searchable()
                                ->nullable()
                                ->default($record->oic_profile_id)
                                ->columnSpanFull()
                                ->visible(fn (Forms\Get $get): bool => (bool) $get('oic_active'))
                                ->requiredIf('oic_active', true)
                                ->helperText('Required when OIC is activated.'),
                        ];
                    })
                    ->fillForm(fn (Division $record): array => [
                        'head_profile_id' => $record->head_profile_id,
                        'oic_active'      => $record->oic_active,
                        'oic_profile_id'  => $record->oic_profile_id,
                    ])
                    ->action(function (Division $record, array $data): void {
                        $record->update([
                            'head_profile_id' => $data['head_profile_id'],
                            'oic_active'      => $data['oic_active'],
                            'oic_profile_id'  => $data['oic_active'] ? $data['oic_profile_id'] : null,
                        ]);
                    })
                    ->modalHeading(fn (Division $record): string => 'Assign — '.$record->name)
                    ->modalWidth('lg')
                    ->successNotificationTitle('Supervisor assignment saved'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupervisorManagement::route('/'),
        ];
    }
}
```

### 6b. Default list page

Create `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/ListSupervisorManagement.php`:

```php
<?php

namespace App\Filament\Hris\Resources\SupervisorManagementResource\Pages;

use App\Filament\Hris\Resources\SupervisorManagementResource;
use Filament\Resources\Pages\ListRecords;

class ListSupervisorManagement extends ListRecords
{
    protected static string $resource = SupervisorManagementResource::class;
}
```

---

## Step 7 — Clean Up Auto-Generated Files

The `make:filament-resource` command generates extra page files. Delete:
- `app/Filament/Hris/Resources/StructureManagementResource/Pages/CreateStructureManagement.php`
- `app/Filament/Hris/Resources/StructureManagementResource/Pages/EditStructureManagement.php`
- `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/CreateSupervisorManagement.php`
- `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/EditSupervisorManagement.php`

Keep only:
- `ListOrgStructure.php` (replaced in step 5b)
- `ListSupervisorManagement.php` (created in step 6b)

---

## Step 8 — Final Commands

Run in order:

```bash
# Format all changed files
vendor/bin/pint --dirty

# Clear all caches
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## Step 9 — Verification Checklist

After implementation, verify each of the following:

1. **Migration ran** — `divisions` table has columns `parent_id`, `type`, `sort_order`, `head_profile_id`, `oic_active`, `oic_profile_id`
2. **Seeder ran** — `Division::find(2)->type` returns `'ogm'`; `Division::find(19)->parent_id` is not null
3. **Navigation** — HRIS panel shows "Organization Management" group with "Structure Management" and "Supervisor Management" items
4. **Structure Management page loads** — Visual tree renders without errors; all 22 nodes appear
5. **Drag-and-drop** — Dragging a Division node onto an ODM node updates `parent_id` in DB; dragging an OAGM onto a Division shows warning notification and reverts
6. **Add Org Unit** — Header action opens modal; saving creates a new Division row
7. **Supervisor Management table** — Lists all 22 org units; type badges show correct color per level
8. **Assign action** — Opening modal pre-fills existing head/OIC; toggling "Activate OIC" ON shows OIC select; saving updates DB correctly
9. **OIC logic** — `Division::find(n)->getActiveSignatory()` returns OIC when `oic_active=true` and OIC assigned; returns head otherwise
10. **Signature chain** — `Division::find(n)->signatureChain()` returns a Collection of Profile models from division to root, each being the active signatory
11. **No profile FK breakage** — `Profile::find(1)->division` still works; all existing `division_id` values resolve correctly

---

## Notes & Pitfalls

- **Two resources, same model:** Both resources target `Division::class`. This is valid in Filament v3 when `$slug` values differ. Do NOT set `$panel` or route them to the same URL.
- **SortableJS CDN:** The `@push('scripts')` requires the Filament layout to have a `@stack('scripts')` — if it does not exist in the HRIS panel layout, load SortableJS inline in the page view instead using `<script src="..." defer></script>` directly above the `<div class="org-tree">`.
- **`wire:key` on tree nodes:** Required for Livewire to correctly diff the DOM after `loadTree()` updates. Ensure every `<li>` in the partial has `wire:key="org-node-{{ $node['id'] }}"`.
- **Recursive partial cannot self-include in Blade:** The partial `org-tree-node.blade.php` calls `@include('filament.hris.partials.org-tree-node', ...)` recursively. This works in Laravel — Blade evaluates includes dynamically, not statically.
- **`HasRecursiveRelationships` default key:** The package assumes the parent key column is named `parent_id`. Our migration uses exactly that name — no override needed.
- **SoftDeletes + tree:** `Division::roots()` scope uses `withTrashed(false)` equivalent from Eloquent; soft-deleted nodes are excluded automatically from `HasRecursiveRelationships` queries.
- **`canBeChildOf(null)`** — Passing `null` as parent type means "place at root." Only `ogm` type nodes have an empty `ALLOWED_PARENT_TYPES` array, so only they can be roots. OBD (id=1) is also `ogm` type and sits at root — this is intentional.
- **Pint** — After all files are written, run `vendor/bin/pint --dirty` once. Do not run `--test` mode.
