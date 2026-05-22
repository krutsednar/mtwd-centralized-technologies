# Implementation Prompt — Organization Management (Resume from Partial State)

## Context

You are continuing a partially-completed implementation in a **Laravel 12 / Filament v3 / Livewire v3 / PostgreSQL** application located at the current working directory. Read every file mentioned before touching it. Follow pint formatting at the end.

---

## What Is Already Done — Do NOT redo these

The following work is complete. Verify by reading the files — do not re-run these steps.

1. **Package installed:** `staudenmeir/laravel-adjacency-list` is in `composer.json`.

2. **Migration ran:** `divisions` table already has columns:
   - `parent_id` (nullable FK → divisions.id)
   - `type` (enum: ogm, oagm, odm, division)
   - `sort_order` (integer)
   - `head_profile_id` (nullable FK → profiles.id)
   - `oic_active` (boolean)
   - `oic_profile_id` (nullable FK → profiles.id)

3. **Seeder ran:** All 22 division rows have correct `type` and `parent_id` values already in the database. Do not re-seed.

4. **`app/Models/Division.php` is fully updated.** It has `HasRecursiveRelationships`, `TYPE_*` constants, `TYPE_LABELS`, `ALLOWED_PARENT_TYPES`, relationships (`head`, `oic`, `profiles`), `getActiveSignatory()`, `signatureChain()`, `canBeChildOf()`, `scopeRoots()`. Do not touch this file.

5. **`app/Filament/Hris/Resources/StructureManagementResource.php` is fully written.** It targets `Division::class`, has `$slug = 'org-structure'`, navigation group `'Organization Management'`, label `'Structure Management'`, and its `getPages()` already references `Pages\ListOrgStructure::route('/')`. Do not touch this file.

---

## What Still Needs to Be Implemented

### Task A — Replace the auto-generated list page stub

The file `app/Filament/Hris/Resources/StructureManagementResource/Pages/ListStructureManagement.php` was auto-generated and is a useless stub. You must:

1. **Delete** `app/Filament/Hris/Resources/StructureManagementResource/Pages/ListStructureManagement.php`
2. **Delete** `app/Filament/Hris/Resources/StructureManagementResource/Pages/CreateStructureManagement.php`
3. **Delete** `app/Filament/Hris/Resources/StructureManagementResource/Pages/EditStructureManagement.php`
4. **Create** `app/Filament/Hris/Resources/StructureManagementResource/Pages/ListOrgStructure.php` with the full content below.

#### `ListOrgStructure.php` — full content

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
        $nodes = Division::query()
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

    // ── Header actions ───────────────────────────────────────────────────────

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

---

### Task B — Create the tree Blade view

Create `resources/views/filament/hris/pages/org-structure.blade.php`:

```blade
<x-filament-panels::page>
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

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    function orgTree(wire) {
        return {
            sortables: [],

            init() {
                this.$nextTick(() => this.initSortable());

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
                        handle:     '.drag-handle',
                        animation:  150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',

                        onEnd: (evt) => {
                            const nodeId = parseInt(evt.item.dataset.id);

                            const rawParent = evt.to.dataset.parentId;
                            const newParentId = (rawParent === 'null' || rawParent === null)
                                ? null
                                : parseInt(rawParent);

                            if (evt.from === evt.to) {
                                const ids = [...evt.to.querySelectorAll(':scope > .org-node')]
                                    .map(li => parseInt(li.dataset.id));
                                wire.reorder(ids, newParentId);
                            } else {
                                wire.reparent(nodeId, newParentId, evt.newIndex);
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

---

### Task C — Create the recursive tree node partial

Create `resources/views/filament/hris/partials/org-tree-node.blade.php`.

You must create the `partials` directory inside `resources/views/filament/hris/` if it does not exist.

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

    {{-- Always render a children container — needed as drag target even when empty --}}
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
</li>
```

---

### Task D — Create SupervisorManagementResource

Run this artisan command first:

```bash
php artisan make:filament-resource SupervisorManagement --panel=hris --no-interaction
```

Then **replace** `app/Filament/Hris/Resources/SupervisorManagementResource.php` entirely with:

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

---

### Task E — Create SupervisorManagement list page, delete stubs

After the artisan command in Task D creates stub files, do the following:

**Delete these auto-generated stubs:**
- `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/CreateSupervisorManagement.php`
- `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/EditSupervisorManagement.php`
- `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/ListSupervisorManagement.php`

**Create** `app/Filament/Hris/Resources/SupervisorManagementResource/Pages/ListSupervisorManagement.php` with this content:

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

### Task F — Final commands

Run these in order:

```bash
vendor/bin/pint --dirty
php artisan filament:optimize-clear
php artisan filament:optimize
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

---

## Verification Checklist

After all tasks complete, verify:

1. **Navigation** — HRIS panel shows "Organization Management" group with two items: "Structure Management" and "Supervisor Management".
2. **Structure Management page loads** — Visual tree renders all org units as draggable cards with indented children.
3. **Type badges** — Each card shows a color-coded badge: blue=OGM, purple=OAGM, yellow=ODM, green=Division.
4. **Drag-and-drop reorder** — Dragging a node within its own parent updates `sort_order` in DB.
5. **Drag-and-drop reparent** — Dragging a Division onto an ODM moves it (updates `parent_id`). Dragging an OAGM onto a Division shows a warning notification and reverts.
6. **Add Org Unit** — Header action modal works; saving creates a new row.
7. **Edit Unit** — Selecting an org unit pre-fills name and abbreviation; saving updates the row.
8. **Supervisor Management table** — All 22 org units are listed with type badge, name, head, OIC Active, and OIC columns.
9. **Assign action** — Opens modal pre-filled with existing values. Toggling "Activate OIC" ON reveals the OIC select. Toggling OFF hides it.
10. **OIC logic in DB** — After saving with `oic_active = false`, `oic_profile_id` is set to null.
11. **No FK breakage** — `Profile::find(1)->division` still resolves (existing `division_id` values are untouched).

---

## Critical Pitfalls — Read Before Writing

- **Two resources, one model:** Both `StructureManagementResource` and `SupervisorManagementResource` target `Division::class`. This is valid in Filament v3 only because their `$slug` values differ (`org-structure` vs `supervisor-management`). Do not change the slugs.
- **`$view` path in `ListOrgStructure`:** Must be `'filament.hris.pages.org-structure'` — this maps to `resources/views/filament/hris/pages/org-structure.blade.php`. If the `pages` directory does not exist, create it.
- **Recursive Blade partial:** `org-tree-node.blade.php` calls `@include('filament.hris.partials.org-tree-node', ...)` on itself. This is valid in Laravel — Blade resolves `@include` dynamically at runtime, not statically. Do not attempt to extract it to a Livewire component.
- **SortableJS inline script:** The `<script src="...sortablejs...">` tag is placed directly in the Blade view — NOT via `@push('scripts')` — because Filament's panel layout may not include a `@stack('scripts')`. Place it just before the `<script>` block that defines `orgTree()`.
- **`wire:key` on every `<li>`:** Required for Livewire to correctly reconcile the DOM after `loadTree()` triggers a re-render. The partial already includes `wire:key="org-node-{{ $node['id'] }}"` on the `<li>` — do not remove it.
- **`orderByDepth` direction in `signatureChain()`:** `'desc'` means the deepest node (the division itself) is first, the root is last. This is intentional — signature chains go from the employee's unit upward.
- **`canBeChildOf(null)`:** `null` parent type = root placement. Only `ogm` type has an empty `ALLOWED_PARENT_TYPES` entry, so only OGM nodes may be roots. Attempting to make a Division a root will be rejected.
- **Run `vendor/bin/pint --dirty`** after all files are written. Do not use `--test` flag. Do not skip this step.
- **Do not run migrations or seeders again** — they already ran successfully. Rerunning the seeder would corrupt sort_order values.
