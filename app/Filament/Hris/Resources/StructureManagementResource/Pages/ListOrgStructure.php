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
                'id' => $n->id,
                'name' => $n->name,
                'abbreviation' => $n->abbreviation,
                'type' => $n->type,
                'type_label' => Division::TYPE_LABELS[$n->type] ?? $n->type,
                'head_name' => $n->head?->full_name,
                'oic_active' => $n->oic_active,
                'oic_name' => $n->oic?->full_name,
                'children' => $this->buildTreeArray($nodes, $n->id),
            ])
            ->toArray();
    }

    // ── Drag-and-drop actions (called from JS via $wire) ─────────────────────

    /**
     * Reorder siblings after a drag within the same parent.
     *
     * @param  array<int>  $orderedIds  IDs in new order
     * @param  int|null  $parentId  The common parent (null = root)
     */
    public function reorder(array $orderedIds, ?int $parentId): void
    {
        foreach ($orderedIds as $index => $id) {
            Division::where('id', $id)->update([
                'sort_order' => $index,
                'parent_id' => $parentId,
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
            'parent_id' => $newParentId,
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
                        ->maxLength(255),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('abbreviation')
                            ->label('Abbreviation')
                            ->required()
                            ->maxLength(30),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Division::TYPE_LABELS)
                            ->required(),
                    ]),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Org Unit')
                        ->options(
                            Division::orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    $node = Division::create([
                        'name' => $data['name'],
                        'abbreviation' => $data['abbreviation'],
                        'type' => $data['type'],
                        'parent_id' => $data['parent_id'] ?? null,
                        'sort_order' => 999,
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
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('abbreviation')
                        ->label('Abbreviation')
                        ->required()
                        ->maxLength(30),
                ])
                ->action(function (array $data): void {
                    Division::where('id', $data['id'])->update([
                        'name' => $data['name'],
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
