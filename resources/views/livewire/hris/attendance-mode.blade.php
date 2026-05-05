<div x-data="{
    countdown: 5,
    showTimer: false,
    startRedirect() {
        this.showTimer = true;
        let timer = setInterval(() => {
            this.countdown--;
            if(this.countdown <= 0) {
                clearInterval(timer);
                $wire.dispatch('resetToMenu');
            }
        }, 1000);
    }
}"
{{-- Syncing the event name to match your PHP dispatch --}}
@attendance-completed.window="startRedirect()">

    {{-- 5-Second Redirect Overlay --}}
    <template x-if="showTimer">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/95 backdrop-blur-xl">
            <div class="text-center">
                <div class="relative inline-flex items-center justify-center mb-6">
                    <svg class="w-32 h-32 text-blue-500">
                        <circle class="opacity-25" cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="none"></circle>
                        <circle class="transition-all duration-1000 opacity-75" cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="none"
                                stroke-dasharray="377" :stroke-dashoffset="377 - (377 * (countdown / 5))"></circle>
                    </svg>
                    <span class="absolute text-4xl font-black text-white" x-text="countdown"></span>
                </div>
                <h2 class="text-2xl font-black tracking-widest text-white uppercase">Verification Successful</h2>
                <p class="mt-2 text-slate-400">Returning to main menu...</p>
                <button @click="$wire.dispatch('resetToMenu')" class="px-6 py-2 mt-8 text-xs font-bold tracking-widest text-white uppercase transition-colors border rounded-full border-slate-700 hover:bg-slate-800">
                    Skip Wait
                </button>
            </div>
        </div>
    </template>

    <div class="max-w-6xl mx-auto mt-8 overflow-hidden bg-gray-100 border border-gray-300 shadow-2xl rounded-xl"
         x-data="attendanceClock($wire)"
         x-init="init()">

        <div class="flex items-center justify-between p-4 text-white bg-slate-800">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <h1 class="text-lg font-bold tracking-wider uppercase">MTWD Biometric Terminal</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 text-xs font-black bg-blue-600 rounded-full animate-pulse">
                    MODE: {{ str_replace('_', ' ', strtoupper($phase ?? 'NOT SET')) }}
                </span>
                <div class="font-mono text-sm" x-text="new Date().toLocaleString()"></div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row min-h-[650px]">
            {{-- LEFT COLUMN --}}
            <div class="w-full md:w-[40%] bg-white p-6 border-r border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold tracking-widest text-black uppercase text-m">POSITION FACE</h3>
                    <button wire:click="$dispatch('resetToMenu')" class="text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors uppercase">
                        ✕ Cancel
                    </button>
                </div>

                <div class="mb-4 h-14">
                    <template x-if="!modelsLoaded">
                        <div class="flex items-center justify-center h-full text-xs italic text-gray-400 border rounded bg-gray-50">
                            Initializing Proximity Sensors...
                        </div>
                    </template>
                    <template x-if="processing">
                        <div class="flex items-center justify-center h-full font-black text-white bg-blue-600 rounded-lg shadow-lg animate-pulse">
                            ANALYZING BIOMETRICS...
                        </div>
                    </template>
                </div>

                {{-- CRITICAL: wire:ignore stops Livewire from killing the video stream --}}
                <div wire:ignore class="relative overflow-hidden bg-black shadow-2xl rounded-2xl ring-8 ring-gray-50 aspect-square">
                    <video x-ref="video" autoplay playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
                    <canvas x-ref="canvas" class="hidden"></canvas>

                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div :class="[
                                 processing ? 'border-blue-500 scale-105 opacity-100 border-4' : 'border-2',
                                 (!processing && isCloseEnough) ? 'border-green-500 opacity-80' : 'border-white opacity-20'
                               ]"
                             class="w-[75%] h-[80%] border-dashed rounded-[60px] transition-all duration-300">
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Attendance Phase</label>
                    <select wire:model.live="phase" class="w-full p-4 mt-2 text-sm font-bold border-gray-200 rounded-xl bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                        <option value="morning_in">Morning In</option>
                        <option value="morning_out">Morning Out</option>
                        <option value="afternoon_in">Afternoon In</option>
                        <option value="afternoon_out">Afternoon Out</option>
                        <option value="ot_in">Overtime In</option>
                        <option value="ot_out">Overtime Out</option>
                    </select>
                </div>

                <div class="mt-4">
                    @if (session()->has('success'))
                        <div class="p-4 text-sm font-bold text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-md animate-bounce">
                            {{ session('success') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="w-full md:w-[60%] bg-slate-50 p-8 relative">
                {{-- Profile and Table content remains same... --}}
                {{-- (Keep your existing Profile and Table code here) --}}
            </div>
        </div>
    </div>

    {{-- Face API Script - Use standard load to ensure availability --}}
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attendanceClock', ($wire) => ({
                processing: false,
                modelsLoaded: false,
                isCloseEnough: false,
                lastCapture: 0,
                cooldown: 8000,
                stream: null,

                async init() {
                    await this.loadModels();
                    await this.startCamera();
                },

                async loadModels() {
                    const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
                    try {
                        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                        this.modelsLoaded = true;
                    } catch (e) { console.error("Model Load Error", e); }
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: { ideal: 640 },
                                height: { ideal: 480 },
                                facingMode: 'user'
                            }
                        });
                        if(this.$refs.video) {
                            this.$refs.video.srcObject = this.stream;
                            this.$refs.video.onloadedmetadata = () => this.proximityLoop();
                        }
                    } catch (err) { console.error("Camera Error:", err); }
                },

                async proximityLoop() {
                    // Stop loop if timer overlay is showing
                    if (this.showTimer || this.processing || !this.modelsLoaded) {
                        return setTimeout(() => requestAnimationFrame(() => this.proximityLoop()), 500);
                    }

                    const video = this.$refs.video;
                    if (!video || video.readyState < 2) return requestAnimationFrame(() => this.proximityLoop());

                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions());

                    if (detection) {
                        // Calculate face size relative to video height
                        this.isCloseEnough = (detection.box.height / video.videoHeight) > 0.4;

                        if (this.isCloseEnough && (Date.now() - this.lastCapture > this.cooldown)) {
                            await this.autoCapture();
                        }
                    } else {
                        this.isCloseEnough = false;
                    }

                    requestAnimationFrame(() => this.proximityLoop());
                },

                async autoCapture() {
                    this.processing = true;
                    this.lastCapture = Date.now();

                    const canvas = this.$refs.canvas;
                    const video = this.$refs.video;

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');

                    // Mirror the capture to match the video preview
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0);

                    const imageData = canvas.toDataURL('image/jpeg', 0.7);

                    // Call Livewire
                    await $wire.recordAttendance(imageData);

                    // Component will stay in 'processing' mode until Livewire finishes
                    setTimeout(() => { this.processing = false; }, 3000);
                }
            }));
        });
    </script>
</div>
