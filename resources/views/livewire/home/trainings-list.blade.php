<div class="space-y-5">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
        </div>
    @else

    {{-- Stats strip --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 dark:from-blue-500/20 dark:to-cyan-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-book-open class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $trainings->total() }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Records</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-clock class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalHrs, 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Hours</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-48">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            </div>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search title or conducted by…"
                   class="w-full text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 pl-9 pr-9 py-2 outline-none transition-colors" />
            <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="w-4 h-4 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>
        </div>
        <div>
            <select wire:model.live="ldType"
                    class="text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 px-3 py-2 outline-none transition-colors w-44">
                <option value="">All L&D Types</option>
                @foreach ($ldTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm" wire:loading.class="opacity-50">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">From</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">To</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Hours</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conducted By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Certificate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                    @forelse ($trainings as $t)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $t->title ?: '—' }}</td>
                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $t->from ? \Carbon\Carbon::parse($t->from)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $t->to ? \Carbon\Carbon::parse($t->to)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                            {{ $t->number_of_hours ? $t->number_of_hours.' hrs' : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $t->conducted_by ?: '—' }}</td>
                        <td class="px-5 py-2.5">
                            @if ($t->ld_type)
                            <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-700/50 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">
                                {{ $t->ld_type }}
                            </span>
                            @else
                            <span class="text-gray-300 dark:text-gray-700">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-center">
                            @if ($t->attachment)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($t->attachment) }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                    <x-heroicon-o-document-text class="w-3.5 h-3.5" /> View
                                </a>
                            @else
                                <span class="text-gray-300 dark:text-gray-700">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-2">
                            <x-home.empty-state label="No training records found." icon="heroicon-o-book-open" class="border-0 rounded-none shadow-none" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-1 border-t border-gray-100 dark:border-gray-800 pt-3">{{ $trainings->links() }}</div>

    @endif
</div>
