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
