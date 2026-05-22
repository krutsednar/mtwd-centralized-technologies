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
