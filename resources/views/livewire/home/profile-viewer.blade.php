<div x-data="{ tab: 'primary' }" wire:loading.class="opacity-50 pointer-events-none">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
            <p class="text-xs text-amber-500 mt-1">Please contact your HR administrator.</p>
        </div>
    @else

    {{-- ── Hero header ──────────────────────────────────────────────────── --}}
    <div class="relative rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm mb-5">
        <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500"></div>
        <div class="p-5 flex flex-col sm:flex-row items-center sm:items-start gap-5">

            {{-- Avatar --}}
            <div class="relative shrink-0">
                <div class="absolute -inset-1.5 rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 opacity-20 dark:opacity-30 blur-sm"></div>
                <div class="relative">
                    @if ($profile->picture)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->picture) }}"
                             alt="{{ $profile->full_name }}"
                             class="w-28 h-28 rounded-full object-cover ring-2 ring-blue-400/50 dark:ring-blue-500/50 ring-offset-2 ring-offset-white dark:ring-offset-gray-900" />
                    @else
                        <div class="w-28 h-28 rounded-full bg-gradient-to-br from-blue-500/15 to-cyan-500/15 dark:from-blue-900/60 dark:to-cyan-900/60 border-2 border-blue-200 dark:border-blue-800/60 flex items-center justify-center ring-2 ring-blue-400/20 dark:ring-blue-500/20 ring-offset-2 ring-offset-white dark:ring-offset-gray-900">
                            <x-heroicon-o-user class="w-12 h-12 text-blue-400 dark:text-blue-500" />
                        </div>
                    @endif
                </div>
            </div>

            {{-- Name / meta --}}
            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $profile->full_name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 font-mono">{{ $profile->employee_number }}</p>

                <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-2">
                    @if ($profile->status)
                        @php
                            $s = $profile->status;
                            $statusClass = match(true) {
                                str_contains($s, 'Active')                                    => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/50 dark:border-emerald-700/50 dark:text-emerald-400',
                                str_contains($s, 'Inactive') || str_contains($s, 'Resigned') => 'bg-red-50 border-red-200 text-red-700 dark:bg-red-950/50 dark:border-red-700/50 dark:text-red-400',
                                default                                                       => 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950/50 dark:border-blue-700/50 dark:text-blue-400',
                            };
                            $dotClass = match(true) {
                                str_contains($s, 'Active')                                    => 'bg-emerald-500 dark:bg-emerald-400',
                                str_contains($s, 'Inactive') || str_contains($s, 'Resigned') => 'bg-red-500 dark:bg-red-400',
                                default                                                       => 'bg-blue-500 dark:bg-blue-400',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }} inline-block"></span>
                            {{ $profile->status }}
                        </span>
                    @endif
                    @if ($profile->division)
                        <span class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                            {{ $profile->division->name }}
                        </span>
                    @endif
                    @if ($profile->pds)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($profile->pds) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 px-3 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950/70 transition-colors">
                            <x-heroicon-o-document-text class="w-3.5 h-3.5" />
                            View PDS
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tab bar ──────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 flex-wrap p-1 rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60 mb-5 overflow-x-auto">
        @foreach ([
            'primary'     => ['label' => 'Primary Info',    'icon' => 'heroicon-o-user'],
            'education'   => ['label' => 'Education',       'icon' => 'heroicon-o-academic-cap'],
            'eligibility' => ['label' => 'Eligibility',     'icon' => 'heroicon-o-check-badge'],
            'work'        => ['label' => 'Work Experience', 'icon' => 'heroicon-o-briefcase'],
            'trainings'   => ['label' => 'Trainings',       'icon' => 'heroicon-o-book-open'],
            'service'     => ['label' => 'Service Records', 'icon' => 'heroicon-o-clipboard-document-list'],
        ] as $key => $meta)
            <button type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm border border-gray-200 dark:border-gray-700 font-semibold'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-800/40 border border-transparent'"
                    class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all duration-200">
                @svg($meta['icon'], 'w-4 h-4 shrink-0')
                {{ $meta['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════ TAB PANELS ═══════════════════════ --}}

    {{-- ── PRIMARY INFO ─────────────────────────────────────────────────── --}}
    <div x-show="tab === 'primary'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="space-y-5">

        @php
            $personalFields = [
                ['label' => 'Date of Birth',   'value' => $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->format('F d, Y') : null],
                ['label' => 'Place of Birth',  'value' => $profile->place_of_birth],
                ['label' => 'Sex',             'value' => $profile->sex === 'M' ? 'Male' : ($profile->sex === 'F' ? 'Female' : $profile->sex)],
                ['label' => 'Citizenship',     'value' => $profile->citizenship],
                ['label' => 'Email',           'value' => $profile->email],
                ['label' => 'Mobile Number',   'value' => $profile->mobile_number ? '+63 '.$profile->mobile_number : null],
                ['label' => 'Present Address', 'value' => $profile->present_address],
            ];
            $idFields = [
                ['label' => 'GSIS No.',        'value' => $profile->gsis_id_no],
                ['label' => 'PAGIBIG No.',     'value' => $profile->pagibig_id_no],
                ['label' => 'PhilHealth No.',  'value' => $profile->philhealth_no],
                ['label' => 'SSS No.',         'value' => $profile->sss_no],
                ['label' => 'TIN No.',         'value' => $profile->tin_no],
            ];
        @endphp

        {{-- Personal Information --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500/15 to-cyan-500/15 dark:from-blue-500/25 dark:to-cyan-500/25 flex items-center justify-center">
                    <x-heroicon-o-user class="w-4 h-4 text-blue-500 dark:text-blue-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Personal Information</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-px bg-gray-100 dark:bg-gray-800">
                @foreach ($personalFields as $field)
                <div class="bg-white dark:bg-gray-900 px-5 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">{{ $field['label'] }}</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $field['value'] ?: '—' }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Government IDs --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-500/15 to-purple-500/15 dark:from-violet-500/25 dark:to-purple-500/25 flex items-center justify-center">
                    <x-heroicon-o-identification class="w-4 h-4 text-violet-500 dark:text-violet-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Government IDs</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-px bg-gray-100 dark:bg-gray-800">
                @foreach ($idFields as $field)
                <div class="bg-white dark:bg-gray-900 px-5 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">{{ $field['label'] }}</p>
                    <p class="text-sm font-mono font-medium text-gray-800 dark:text-gray-200">{{ $field['value'] ?: '—' }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Family Background --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-rose-500/15 to-pink-500/15 dark:from-rose-500/25 dark:to-pink-500/25 flex items-center justify-center">
                    <x-heroicon-o-heart class="w-4 h-4 text-rose-500 dark:text-rose-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Family Background</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-gray-100 dark:bg-gray-800">
                @foreach ([
                    'Spouse' => [$profile->spouse_first_name, $profile->spouse_middle_name, $profile->spouse_surname],
                    'Father' => [$profile->father_first_name, $profile->father_middle_name, $profile->father_surname],
                    'Mother' => [$profile->mother_first_name, $profile->mother_middle_name, $profile->mother_surname],
                ] as $relation => $parts)
                    <div class="bg-white dark:bg-gray-900 px-5 py-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ $relation }}</p>
                        @php $name = trim(implode(' ', array_filter($parts))); @endphp
                        @if ($name)
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $name }}</p>
                        @else
                            <p class="text-sm text-gray-300 dark:text-gray-600 italic">Not provided</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Children --}}
        @if ($profile->children->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500/15 to-orange-500/15 dark:from-amber-500/25 dark:to-orange-500/25 flex items-center justify-center">
                    <x-heroicon-o-user-group class="w-4 h-4 text-amber-500 dark:text-amber-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Children</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date of Birth</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @foreach ($profile->children as $child)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $child->name ?: '—' }}</td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">
                            {{ $child->date_of_birth ? \Carbon\Carbon::parse($child->date_of_birth)->format('F d, Y') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Skills --}}
        @if ($profile->skills->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-cyan-500/15 to-teal-500/15 dark:from-cyan-500/25 dark:to-teal-500/25 flex items-center justify-center">
                    <x-heroicon-o-sparkles class="w-4 h-4 text-cyan-500 dark:text-cyan-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Special Skills / Hobbies</p>
            </div>
            <div class="px-5 py-4 flex flex-wrap gap-2">
                @foreach ($profile->skills as $skill)
                    <span class="rounded-full bg-cyan-50 dark:bg-cyan-950/50 border border-cyan-200 dark:border-cyan-700/50 px-3 py-1 text-xs font-medium text-cyan-700 dark:text-cyan-300">{{ $skill->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Organizations --}}
        @if ($profile->organizations->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2.5 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-emerald-500/15 to-green-500/15 dark:from-emerald-500/25 dark:to-green-500/25 flex items-center justify-center">
                    <x-heroicon-o-building-office class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                </div>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Membership in Associations / Organizations</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Organization</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Address</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">From</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">To</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @foreach ($profile->organizations as $org)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $org->organization_name ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $org->organization_address ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $org->position_title ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $org->from ? \Carbon\Carbon::parse($org->from)->format('M Y') : '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $org->to ? \Carbon\Carbon::parse($org->to)->format('M Y') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ── EDUCATION ────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'education'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        @if ($profile->educationalBackgrounds->count())
        <div class="space-y-4">
            @foreach ($profile->educationalBackgrounds as $edu)
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $edu->school_name ?: '—' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $edu->degree_course ?: '—' }}</p>
                    </div>
                    @if ($edu->level)
                    <span class="self-start sm:self-center inline-flex rounded-full bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-700/50 px-3 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300 shrink-0">
                        {{ $edu->level }}
                    </span>
                    @endif
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 dark:bg-gray-800">
                    <div class="bg-white dark:bg-gray-900 px-5 py-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Period</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $edu->from ? \Carbon\Carbon::parse($edu->from)->format('Y') : '—' }}
                            – {{ $edu->to ? \Carbon\Carbon::parse($edu->to)->format('Y') : 'Present' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 px-5 py-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Year Graduated</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $edu->year_graduated ?: '—' }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 px-5 py-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Highest Grade</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $edu->highest_grade ?: '—' }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 px-5 py-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Honors</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $edu->honors ?: '—' }}</p>
                    </div>
                </div>
                @if ($edu->tor || $edu->diploma)
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex gap-4">
                    @if ($edu->tor)
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($edu->tor) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        <x-heroicon-o-document-text class="w-3.5 h-3.5" /> Transcript of Records
                    </a>
                    @endif
                    @if ($edu->diploma)
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($edu->diploma) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        <x-heroicon-o-document-text class="w-3.5 h-3.5" /> Diploma
                    </a>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
            <x-home.empty-state label="No education records on file." icon="heroicon-o-academic-cap" />
        @endif
    </div>

    {{-- ── ELIGIBILITY ──────────────────────────────────────────────────── --}}
    <div x-show="tab === 'eligibility'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        @if ($profile->eligibilities->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Eligibility</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rating</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Place</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">License No.</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Date Issued</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">File</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                        @foreach ($profile->eligibilities as $eli)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $eli->eligibility ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $eli->rating ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $eli->date_of_examination ? \Carbon\Carbon::parse($eli->date_of_examination)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $eli->place_of_examination ?: '—' }}</td>
                            <td class="px-5 py-3 font-mono text-gray-500 dark:text-gray-400">{{ $eli->license_no ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $eli->date_issued ? \Carbon\Carbon::parse($eli->date_issued)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if ($eli->attachment)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($eli->attachment) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                        <x-heroicon-o-document-text class="w-3.5 h-3.5" /> View
                                    </a>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <x-home.empty-state label="No eligibility records on file." icon="heroicon-o-check-badge" />
        @endif
    </div>

    {{-- ── WORK EXPERIENCE ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'work'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        @if ($profile->workExperiences->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">From</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">To</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agency</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salary</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SG</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gov't</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">COE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                        @foreach ($profile->workExperiences->sortByDesc('from') as $we)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $we->from ? \Carbon\Carbon::parse($we->from)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                @if ($we->to)
                                    <span class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($we->to)->format('M d, Y') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-700/50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">Present</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $we->position_title ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $we->agency ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                                {{ $we->monthly_salary ? '₱'.number_format($we->monthly_salary, 2) : '—' }}
                            </td>
                            <td class="px-5 py-2.5 text-center text-gray-500 dark:text-gray-400">{{ $we->salary_grade ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $we->appointment_status ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-center">
                                @if ($we->government)
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500 dark:text-emerald-400 mx-auto" />
                                @else
                                    <x-heroicon-o-x-circle class="w-4 h-4 text-gray-300 dark:text-gray-700 mx-auto" />
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-center">
                                @if ($we->coe)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($we->coe) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                        <x-heroicon-o-document-text class="w-3.5 h-3.5" /> View
                                    </a>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <x-home.empty-state label="No work experience records on file." icon="heroicon-o-briefcase" />
        @endif
    </div>

    {{-- ── TRAININGS ────────────────────────────────────────────────────── --}}
    <div x-show="tab === 'trainings'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        @if ($profile->trainings->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
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
                        @foreach ($profile->trainings as $training)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $training->title ?: '—' }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $training->from ? \Carbon\Carbon::parse($training->from)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $training->to ? \Carbon\Carbon::parse($training->to)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                                {{ $training->number_of_hours ? $training->number_of_hours.' hrs' : '—' }}
                            </td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $training->conducted_by ?: '—' }}</td>
                            <td class="px-5 py-2.5">
                                @if ($training->ld_type)
                                <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-700/50 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">
                                    {{ $training->ld_type }}
                                </span>
                                @else
                                <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-center">
                                @if ($training->attachment)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($training->attachment) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                        <x-heroicon-o-document-text class="w-3.5 h-3.5" /> View
                                    </a>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-t border-gray-200 dark:border-gray-700">
                            <td colspan="3" class="px-5 py-2.5 text-xs font-semibold text-right text-gray-500 dark:text-gray-400">Total Training Hours:</td>
                            <td class="px-5 py-2.5 text-right text-sm font-bold text-blue-600 dark:text-blue-400">
                                {{ $profile->trainings->sum('number_of_hours') }} hrs
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
            <x-home.empty-state label="No training records on file." icon="heroicon-o-book-open" />
        @endif
    </div>

    {{-- ── SERVICE RECORDS ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'service'" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        @if ($profile->serviceRecords->count())
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">From</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">To</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agency</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SG</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salary</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Allowance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                        @foreach ($profile->serviceRecords as $sr)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $sr->from ? \Carbon\Carbon::parse($sr->from)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                @if ($sr->to)
                                    <span class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($sr->to)->format('M d, Y') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-700/50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">Present</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $sr->agency ?: '—' }}</td>
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $sr->position ?: '—' }}</td>
                            <td class="px-5 py-2.5">
                                @if ($sr->status)
                                    <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-700/50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">{{ $sr->status }}</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-center text-gray-500 dark:text-gray-400">{{ $sr->sg ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                                {{ $sr->salary ? '₱'.number_format($sr->salary, 2) : '—' }}
                            </td>
                            <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                                {{ $sr->allowance ? '₱'.number_format($sr->allowance, 2) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <x-home.empty-state label="No service records on file." icon="heroicon-o-clipboard-document-list" />
        @endif
    </div>

    @endif
</div>
