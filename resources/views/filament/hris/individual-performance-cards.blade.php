<div class="space-y-4 px-1 py-4">
    @forelse ($individualPerformances as $ipc)
        <div class="group relative overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm transition-all hover:shadow-md">

            {{-- Header Section --}}
            <div class="mb-5 flex items-end justify-between border-b border-gray-50 dark:border-gray-800/60 pb-4">
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Academic Year</span>
                    <h3 class="text-2xl font-black tracking-tight text-gray-900 dark:text-gray-100">
                        {{ $ipc->ipc_year }}
                    </h3>
                </div>
                <div class="hidden sm:block">
                    <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-[10px] font-bold uppercase tracking-tighter text-gray-500 dark:text-gray-300">
                        Performance Record
                    </span>
                </div>
            </div>

            {{-- Grid of Documents --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                {{-- Individual Performance (IPC) --}}
                <div class="relative flex flex-col justify-between rounded-xl border border-gray-50 dark:border-gray-800 bg-gray-50/50 dark:bg-white/5 p-4 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                    <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">IPC Attachment</p>
                    @if ($ipc->ipc_attachment)
                        <a href="{{ Storage::url($ipc->ipc_attachment) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 dark:text-blue-400 group/link">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition-transform group-hover/link:scale-110">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            Download File
                        </a>
                    @else
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-400 dark:text-gray-600 italic">
                            No attachment
                        </span>
                    @endif
                </div>

                {{-- IPCR 1st Semester --}}
                <div class="relative flex flex-col justify-between rounded-xl border border-gray-50 dark:border-gray-800 bg-gray-50/50 dark:bg-white/5 p-4 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                    <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">1st Semester (IPCR)</p>
                    @if ($ipc->ipcr_first)
                        <a href="{{ Storage::url($ipc->ipcr_first) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-400 group/link">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 transition-transform group-hover/link:scale-110">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            View Rating
                        </a>
                    @else
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-400 dark:text-gray-600 italic">
                            Pending
                        </span>
                    @endif
                </div>

                {{-- IPCR 2nd Semester --}}
                <div class="relative flex flex-col justify-between rounded-xl border border-gray-50 dark:border-gray-800 bg-gray-50/50 dark:bg-white/5 p-4 transition-colors hover:bg-gray-100 dark:hover:bg-white/10">
                    <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">2nd Semester (IPCR)</p>
                    @if ($ipc->ipcr_second)
                        <a href="{{ Storage::url($ipc->ipcr_second) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 text-sm font-bold text-amber-600 dark:text-amber-400 group/link">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 transition-transform group-hover/link:scale-110">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            View Rating
                        </a>
                    @else
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-400 dark:text-gray-600 italic">
                            Pending
                        </span>
                    @endif
                </div>

            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-100 dark:border-gray-800 py-12">
            <div class="mb-3 rounded-full bg-gray-50 dark:bg-gray-800/50 p-4">
                <svg class="h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No performance records found.</p>
        </div>
    @endforelse
</div>
