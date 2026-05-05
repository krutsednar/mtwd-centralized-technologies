<div class="max-w-4xl mx-auto space-y-10"
     x-data="{
        currentTime: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        currentDate: new Date().toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
     }"
     x-init="setInterval(() => {
        currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
     }, 1000)">

    {{-- TERMINAL HEADER SECTION --}}
    <div class="flex flex-col items-center text-center">

        {{-- LOGO CONTAINER --}}
        <div class="relative p-1 mb-6 rounded-full bg-slate-900/40 ring-1 ring-slate-700/50 shadow-[0_0_50px_rgba(30,58,138,0.3)]">
            <img src="https://moca.mtwd-kit.ph/img/logomtwd.png"
                 alt="MTWD Logo"
                 class="h-20 w-20 sm:h-24 sm:w-24 object-contain filter drop-shadow-[0_0_8px_rgba(255,255,255,0.2)]">
        </div>

        {{-- DIGITAL CLOCK DISPLAY --}}
        <div class="mb-6">
            <div class="font-mono text-5xl font-black tracking-tighter text-white sm:text-7xl filter drop-shadow-[0_0_15px_rgba(59,130,246,0.5)]"
                 x-text="currentTime">
            </div>
            <div class="mt-2 text-xs font-bold tracking-[0.3em] text-blue-400 uppercase sm:text-sm"
                 x-text="currentDate">
            </div>
        </div>

        {{-- STATUS PILL --}}
        <div class="inline-flex items-center justify-center px-4 py-1 mb-6 space-x-2 border rounded-full bg-slate-900/80 border-slate-700">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
            <span class="text-[10px] font-black tracking-[0.3em] text-slate-300 uppercase">Terminal Online</span>
        </div>

        {{-- TITLES --}}
        <h1 class="text-3xl font-black tracking-tighter text-white uppercase sm:text-5xl">
            MTWD <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-blue-600">Biometric</span>
        </h1>
    </div>

    {{-- BUTTON GRID --}}
    <div class="grid grid-cols-1 gap-4 px-4 md:grid-cols-2 md:gap-6">
        @foreach([
            'morning_in' => ['label' => 'Morning Clock In', 'color' => 'blue'],
            'morning_out' => ['label' => 'Morning Clock Out', 'color' => 'blue'],
            'afternoon_in' => ['label' => 'Afternoon Clock In', 'color' => 'blue'],
            'afternoon_out' => ['label' => 'Afternoon Clock Out', 'color' => 'blue'],
        ] as $key => $config)
            <button wire:click="selectPhase('{{ $key }}')"
                class="relative p-8 overflow-hidden transition-all border shadow-2xl group bg-slate-800/80 border-slate-700 rounded-[32px] hover:border-blue-500 hover:bg-slate-800 hover:scale-[1.02] active:scale-95">

                <div class="relative z-10 flex flex-col items-center justify-center space-y-1">
                    <div class="text-[9px] font-black text-blue-500 uppercase tracking-[0.2em] opacity-60 group-hover:opacity-100 transition-opacity">
                        Attendance Mode
                    </div>
                    <h2 class="text-xl font-black text-white uppercase transition-colors sm:text-2xl group-hover:text-blue-50">
                        {{ $config['label'] }}
                    </h2>
                </div>

                <div class="absolute inset-0 transition-opacity opacity-0 bg-gradient-to-br from-blue-600/15 via-transparent to-transparent group-hover:opacity-100"></div>
            </button>
        @endforeach
    </div>

    {{-- OVERTIME BUTTON GRID --}}
    <div class="px-4">
        <div class="mb-3 text-center">
            <span class="text-[9px] font-black tracking-[0.3em] text-amber-500 uppercase opacity-70">Overtime</span>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6">
            @foreach([
                'ot_in' => 'Overtime In',
                'ot_out' => 'Overtime Out',
            ] as $key => $label)
                <button wire:click="selectPhase('{{ $key }}')"
                    class="relative p-8 overflow-hidden transition-all border shadow-2xl group bg-slate-800/80 border-amber-900/40 rounded-[32px] hover:border-amber-500 hover:bg-slate-800 hover:scale-[1.02] active:scale-95">

                    <div class="relative z-10 flex flex-col items-center justify-center space-y-1">
                        <div class="text-[9px] font-black text-amber-500 uppercase tracking-[0.2em] opacity-60 group-hover:opacity-100 transition-opacity">
                            Overtime Mode
                        </div>
                        <h2 class="text-xl font-black text-white uppercase transition-colors sm:text-2xl group-hover:text-amber-50">
                            {{ $label }}
                        </h2>
                    </div>

                    <div class="absolute inset-0 transition-opacity opacity-0 bg-gradient-to-br from-amber-600/15 via-transparent to-transparent group-hover:opacity-100"></div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- SYSTEM FOOTER --}}
    <div class="pt-6 text-center">
        <p class="text-[9px] font-bold tracking-widest uppercase text-slate-500 opacity-60">
            METROPOLITAN TUGUEGARAO WATER DISTRICT &bull; Face Biometric Attendance System
        </p>
    </div>
</div>
