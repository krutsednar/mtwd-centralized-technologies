<div class="space-y-5">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
        </div>
    @elseif ($performances->isEmpty())
        <x-home.empty-state label="No individual performance records on file." icon="heroicon-o-chart-bar" />
    @else

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($performances as $ipc)
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm hover:border-blue-200 dark:hover:border-blue-800/60 transition-colors">

            {{-- Year header --}}
            <div class="relative overflow-hidden px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-br from-blue-500/5 to-cyan-500/5 dark:from-blue-900/20 dark:to-cyan-900/20">
                <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full bg-blue-500/5 dark:bg-blue-400/10"></div>
                <div class="flex items-start justify-between relative">
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ipc->ipc_year }}</p>
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mt-0.5">Performance Year</p>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 flex items-center justify-center shrink-0">
                        <x-heroicon-o-chart-bar-square class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            {{-- Documents --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                @foreach ([
                    ['label' => 'IPC (Individual Performance Contract)', 'key' => 'ipc_attachment'],
                    ['label' => 'IPCR — 1st Semester',                   'key' => 'ipcr_first'],
                    ['label' => 'IPCR — 2nd Semester',                   'key' => 'ipcr_second'],
                ] as $doc)
                <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-snug">{{ $doc['label'] }}</p>
                    @if ($ipc->{$doc['key']})
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($ipc->{$doc['key']}) }}"
                           target="_blank"
                           class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-700/50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950 transition-colors">
                            <x-heroicon-o-document-text class="w-3.5 h-3.5" />
                            View PDF
                        </a>
                    @else
                        <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 px-2.5 py-0.5 text-xs text-gray-400 dark:text-gray-500">
                            <x-heroicon-o-x-mark class="w-3 h-3" /> Not uploaded
                        </span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    @endif
</div>
