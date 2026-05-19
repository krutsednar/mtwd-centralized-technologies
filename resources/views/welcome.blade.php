<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MTWD Portal | Terminal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-950 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-4xl w-full mx-auto space-y-10"
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
            <div class="mb-4">
                <div class="font-mono text-4xl font-black tracking-tighter text-white lg:text-7xl filter drop-shadow-[0_0_15px_rgba(59,130,246,0.5)]"
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
                MTWD <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-blue-600">CENTRALIZED TECHNOLOGIES</span>
                {{-- LOGIN BUTTON --}}
                <div class="flex justify-center px-4 pt-6">
                    @auth
                        <a href="{{ url('/home') }}"
                        wire:navigate
                        class="inline-flex items-center gap-3 px-6 py-3 transition-all border group rounded-2xl bg-emerald-500/10 border-emerald-500/30 hover:bg-emerald-500/20 hover:scale-105 active:scale-95">

                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>

                            <span class="text-sm font-black tracking-[0.2em] text-emerald-300 uppercase">
                                Home
                            </span>
                        </a>
                    @else
                        <a href="{{ url('/home/login') }}"
                        wire:navigate
                        class="inline-flex items-center gap-3 px-6 py-3 transition-all border group rounded-2xl bg-amber-500/10 border-amber-500/30 hover:bg-amber-500/20 hover:scale-105 active:scale-95">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-amber-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5m0 0l-5-5m5 5H3"/>
                            </svg>

                            <span class="text-sm font-black tracking-[0.2em] text-amger-300 uppercase">
                                Employee Login
                            </span>
                        </a>
                    @endauth
                </div>
            </h1>
        </div>

        {{-- APPLICATION GRID --}}
        <div class="grid grid-cols-1 gap-4 px-4 md:grid-cols-2 md:gap-6">
            @php
                $apps = [
                    [
                        'label' => 'Face Biometrics',
                        'desc' => 'Attendance Terminal',
                        'color' => 'blue',
                        'route' => 'face-biometrics.index',
                    ],
                    [
                        'label' => 'Leave Application',
                        'desc' => 'File Leave Request',
                        'color' => 'emerald',
                        'route' => '#',
                    ],
                    [
                        'label' => 'CTO Application',
                        'desc' => 'Compensatory Time Off',
                        'color' => 'gray',
                        'route' => '#',
                    ],
                    [
                        'label' => 'Overtime Application',
                        'desc' => 'File OT Request',
                        'color' => 'amber',
                        'route' => '#',
                    ],
                ];
            @endphp

            @foreach($apps as $app)
                <a href="{{ Route::has($app['route']) ? route($app['route']) : '#' }}"
                   wire:navigate
                   class="relative p-8 overflow-hidden transition-all border shadow-2xl group bg-slate-900/50 border-slate-700/50 rounded-[32px] hover:border-{{ $app['color'] }}-500/50 hover:bg-slate-800 hover:scale-[1.02] active:scale-95 block">

                    <div class="relative z-10 flex flex-col items-center justify-center space-y-1">
                        <div class="text-[10px] font-black text-{{ $app['color'] }}-500 uppercase tracking-[0.2em] opacity-80 group-hover:opacity-100 transition-opacity">
                            {{ $app['desc'] }}
                        </div>
                        <h2 class="text-xl font-black text-white uppercase transition-colors sm:text-2xl group-hover:text-{{ $app['color'] }}-50">
                            {{ $app['label'] }}
                        </h2>
                    </div>

                    {{-- Hover Background Glow --}}
                    <div class="absolute inset-0 transition-opacity opacity-0 bg-gradient-to-br from-{{ $app['color'] }}-600/10 via-transparent to-transparent group-hover:opacity-100"></div>
                </a>
            @endforeach
        </div>

        {{-- SYSTEM FOOTER --}}
        <div class="pt-8 text-center">
            <p class="text-[9px] font-bold tracking-widest uppercase text-slate-500 opacity-60">
                METROPOLITAN TUGUEGARAO WATER DISTRICT &bull; CENTRALIZED TECHNOLOGIES
            </p>
        </div>
    </div>

</body>
</html>
