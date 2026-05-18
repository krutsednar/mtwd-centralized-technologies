<div class="flex flex-col min-h-screen overflow-x-hidden bg-slate-900"
     x-data="attendanceClock($wire)"
     x-init="init()">

    {{-- TOP BAR: Logo & System Time --}}
    <div class="flex items-center justify-between px-6 py-4 border-b shadow-2xl bg-slate-900 border-slate-800 shrink-0"
         x-data="{ time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) }"
         x-init="setInterval(() => time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}), 1000)">

        <div class="flex items-center space-x-4">
            <img src="https://moca.mtwd-kit.ph/img/logomtwd.png" class="h-10 w-10 object-contain filter drop-shadow-[0_0_5px_rgba(59,130,246,0.5)]">
            <div>
                <h1 class="text-sm font-black leading-none tracking-tighter text-white uppercase sm:text-xl">MTWD Terminal</h1>
                <div class="flex items-center mt-1 space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-[9px] font-bold text-blue-400 uppercase tracking-widest">{{ str_replace('_', ' ', $phase) }}</span>
                </div>
            </div>
        </div>

        <div class="text-right">
            <div class="font-mono text-xl font-black text-white sm:text-3xl" x-text="time"></div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ now()->format('l, F j') }}</div>
        </div>
    </div>

    {{-- MAIN SCANNING AREA --}}
    <div class="flex flex-col items-center justify-center flex-grow p-4 sm:p-8">
        <div class="w-full max-w-lg">

            {{-- BIOMETRIC VIEWPORT --}}
            <div class="relative group">
                {{-- Decorative Corners --}}
                <div class="absolute z-10 w-12 h-12 border-t-4 border-l-4 border-blue-600 -top-2 -left-2 rounded-tl-3xl"></div>
                <div class="absolute z-10 w-12 h-12 border-t-4 border-r-4 border-blue-600 -top-2 -right-2 rounded-tr-3xl"></div>
                <div class="absolute z-10 w-12 h-12 border-b-4 border-l-4 border-blue-600 -bottom-2 -left-2 rounded-bl-3xl"></div>
                <div class="absolute z-10 w-12 h-12 border-b-4 border-r-4 border-blue-600 -bottom-2 -right-2 rounded-br-3xl"></div>

                {{-- Camera Feed --}}
                <div class="relative overflow-hidden bg-black aspect-square rounded-[40px] shadow-[0_0_50px_rgba(0,0,0,0.5)] ring-1 ring-slate-700">
                    <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>
                    <canvas x-ref="canvas" class="hidden"></canvas>

                    {{-- Face Mesh Overlay (Scanning Animation) --}}
                    <div class="absolute inset-0 pointer-events-none opacity-20 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

                    {{-- CHALLENGE OVERLAY --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div :class="isReadyToCapture ? 'border-green-500 scale-105' : 'border-blue-500/30'"
                             class="w-[80%] h-[80%] border-2 border-dashed rounded-[60px] transition-all duration-700 flex flex-col items-center justify-center">

                            <template x-if="isCentered && isClose && !challengeVerified && !processing">
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="p-4 bg-blue-600 rounded-full shadow-lg animate-bounce">
                                        <svg x-show="challengeType === 'LEFT'" class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M15 19l-7-7 7-7" /></svg>
                                        <svg x-show="challengeType === 'RIGHT'" class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M9 5l7 7-7 7" /></svg>
                                    </div>
                                    <span class="px-8 py-2 text-xl font-black text-white uppercase bg-blue-600 rounded-full ring-4 ring-blue-600/30" x-text="`TURN ${challengeType}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div x-show="processing" class="absolute bottom-0 left-0 w-full h-2 bg-blue-900">
                        <div class="h-full bg-blue-500 animate-[progress_1s_infinite]"></div>
                    </div>
                </div>
            </div>

            {{-- BOTTOM CONTROLS --}}
            <div class="mt-10 space-y-4">
                {{-- Status Card --}}
                <div :class="processing ? 'bg-blue-600' : (isReadyToCapture ? 'bg-green-600' : 'bg-slate-800')"
                     class="flex items-center justify-center px-6 py-4 transition-all shadow-xl rounded-3xl">
                    <div class="flex items-center space-x-3">
                        <template x-if="processing">
                            <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </template>
                        <span x-text="processing ? 'AUTHENTICATING...' : statusMessage" class="text-sm font-black tracking-widest text-white uppercase"></span>
                    </div>
                </div>

                <a href="{{ url('attendance-mode') }}" wire:navigate
                   class="block w-full py-4 text-xs font-black tracking-widest text-center uppercase transition-colors text-slate-500 hover:text-white">
                    &larr; Abort Session
                </a>
            </div>
        </div>
    </div>

    {{-- SUCCESS MODAL --}}
    <div x-data="{ open: @entangle('showSuccessModal') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-init="$watch('open', value => { if(value) { new Audio('https://assets.mixkit.co/active_storage/sfx/2013/2013-preview.mp3').play(); setTimeout(() => window.location.href = '{{ url('attendance-mode') }}', 2500); } })"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/95 backdrop-blur-xl" style="display: none;">

        <div class="bg-white rounded-[50px] shadow-2xl max-w-md w-full p-12 text-center border-[12px] border-green-500 relative overflow-hidden">
            <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-green-600 bg-green-100 rounded-full shadow-inner">
                <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M5 13l4 4L19 7" /></svg>
            </div>
            <h3 class="text-4xl font-black tracking-tighter uppercase text-slate-900">Verified</h3>

            <div class="mt-6 mb-8 space-y-4">
                <p class="text-2xl font-black text-blue-600 uppercase">{{ $currentEmployee['name'] }}</p>
                <div class="inline-block px-6 py-2 text-sm font-black tracking-widest text-blue-700 uppercase border border-blue-100 rounded-full bg-blue-50">
                    {{ str_replace('_', ' ', $phase) }}
                </div>
                <div class="font-mono text-2xl font-bold text-slate-400">
                    {{ now()->format('h:i:s A') }}
                </div>
            </div>

            <div class="absolute bottom-0 left-0 w-full h-3 bg-green-500" style="animation: progress 2.5s linear forwards;"></div>
        </div>
    </div>

    {{-- FAIL MODAL --}}
    <div x-data="{ open: @entangle('showFailModal') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-init="$watch('open', value => { if(value) { new Audio('https://assets.mixkit.co/active_storage/sfx/955/955-preview.mp3').play(); setTimeout(() => window.location.href = '{{ url('attendance-mode') }}', 3000); } })"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-red-950/95 backdrop-blur-xl" style="display: none;">

        <div class="bg-white rounded-[50px] shadow-2xl max-w-sm w-full p-12 text-center border-[12px] border-red-500 relative overflow-hidden">
            <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-red-600 bg-red-100 rounded-full">
                <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
            <h3 class="text-3xl font-black tracking-tighter uppercase text-slate-900">Scan Failed</h3>
            <p class="mt-2 font-bold tracking-widest uppercase text-slate-500">Biometric Mismatch</p>
            <div class="absolute bottom-0 left-0 w-full h-3 bg-red-500" style="animation: progress 3s linear forwards;"></div>
        </div>
    </div>

    {{-- DUPLICATE MODAL --}}
    <div x-data="{ open: @entangle('showDuplicateModal') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-init="$watch('open', value => { if(value) { new Audio('https://assets.mixkit.co/active_storage/sfx/955/955-preview.mp3').play(); setTimeout(() => window.location.href = '{{ url('attendance-mode') }}', 3000); } })"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-red-950/95 backdrop-blur-xl" style="display: none;">

        <div class="bg-white rounded-[50px] shadow-2xl max-w-sm w-full p-12 text-center border-[12px] border-red-500 relative overflow-hidden">
            <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-red-600 bg-red-100 rounded-full">
                <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
            <h3 class="text-3xl font-black tracking-tighter uppercase text-slate-900">Already Logged</h3>
            <p class="mt-2 font-bold tracking-widest uppercase text-slate-500">Record already exists</p>
            <div class="absolute bottom-0 left-0 w-full h-3 bg-red-500" style="animation: progress 3s linear forwards;"></div>
        </div>
    </div>

    <style>
        @keyframes progress { from { width: 0%; } to { width: 100%; } }
        body { position: fixed; width: 100%; background: #0f172a; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('attendanceClock', ($wire) => ({
            processing: false,
            modelsLoaded: false,
            isReadyToCapture: false,
            isCentered: false,
            isClose: false,
            statusMessage: 'VERIFYING...',
            challengeType: null,
            challengeVerified: false,

            async init() {
                try {
                    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
                    ]);
                    this.modelsLoaded = true;
                    await this.startCamera();
                } catch (e) { this.statusMessage = 'MODEL ERROR'; }
            },

            async startCamera() {
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.statusMessage = 'HTTPS REQUIRED';
                    return;
                }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 640, height: 480, facingMode: "user" }
                    });
                    this.$refs.video.srcObject = stream;
                    this.$refs.video.onloadedmetadata = () => this.scanLoop();
                } catch (err) {
                    this.statusMessage = err.name === 'NotAllowedError' ? 'CAMERA DENIED' : 'CAMERA ERROR';
                }
            },

            async scanLoop() {
                // Stop loop if processing or a modal is already shown
                if (this.processing || !this.modelsLoaded || $wire.showSuccessModal || $wire.showFailModal || $wire.showDuplicateModal) {
                    return requestAnimationFrame(() => this.scanLoop());
                }

                const detection = await faceapi.detectSingleFace(this.$refs.video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224 })).withFaceLandmarks();

                if (detection) {
                    if (!this.challengeType) this.challengeType = Math.random() > 0.5 ? 'RIGHT' : 'LEFT';

                    const box = detection.detection.box;
                    const videoW = this.$refs.video.videoWidth;

                    this.isClose = (box.height / this.$refs.video.videoHeight) > 0.35;
                    this.isCentered = Math.abs((box.x + (box.width / 2)) - (videoW / 2)) < (videoW * 0.15);

                    if (this.isClose && this.isCentered && !this.challengeVerified) {
                        const landmarks = detection.landmarks;
                        const nose = landmarks.getNose()[6];
                        const leftEye = landmarks.getLeftEye()[0];
                        const rightEye = landmarks.getRightEye()[3];
                        const hRatio = (nose.x - leftEye.x) / Math.abs(rightEye.x - leftEye.x);

                        if (this.challengeType === 'LEFT' && hRatio > 0.70) this.challengeVerified = true;
                        if (this.challengeType === 'RIGHT' && hRatio < 0.30) this.challengeVerified = true;
                    }

                    if (!this.isClose) this.statusMessage = "MOVE CLOSER";
                    else if (!this.isCentered) this.statusMessage = "CENTER FACE";
                    else if (!this.challengeVerified) this.statusMessage = `TURN ${this.challengeType}`;
                    else {
                        this.isReadyToCapture = true;
                        await this.autoCapture();
                        return;
                    }
                }
                requestAnimationFrame(() => this.scanLoop());
            },

            async autoCapture() {
                this.processing = true;
                const canvas = this.$refs.canvas;
                canvas.width = this.$refs.video.videoWidth;
                canvas.height = this.$refs.video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(this.$refs.video, 0, 0);

                await $wire.recordAttendance(canvas.toDataURL('image/jpeg', 0.8));
                // We don't set processing false here; the modal redirect handles the "reset"
            }
        }));
    });
    </script>
</div>
