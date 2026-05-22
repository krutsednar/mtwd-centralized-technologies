@push('head-scripts')
<script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
<style>
    @keyframes fb-progress { 0% { width: 0%; } 100% { width: 100%; } }
</style>
@endpush

{{-- Outer div is the Livewire root. Inner div owns Alpine to avoid $wire toJSON conflict. --}}
<div>
    <div class="flex flex-col min-h-screen overflow-x-hidden bg-white"
         x-data="faceBiometricsClock(@js([
             'modelUrl' => asset('vendor/face-api/weights'),
             'wireId'   => $this->getId(),
         ]))"
         x-init="init()"
         wire:ignore>

        {{-- TOP BAR: Logo & System Time --}}
        <div class="flex items-center justify-between px-6 py-4 border-b shadow-2xl bg-white border-slate-800 shrink-0">
            <div class="flex items-center space-x-4">
                <img src="https://moca.mtwd-kit.ph/img/logomtwd.png"
                     class="h-10 w-10 object-contain filter drop-shadow-[0_0_5px_rgba(59,130,246,0.5)]">
                <div>
                    <h1 class="text-sm font-black leading-none tracking-tighter text-blue-800 uppercase sm:text-xl">MTWD Face Terminal</h1>
                    <div class="flex items-center mt-1 space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-[9px] font-bold text-blue-700 uppercase tracking-widest">Face Biometrics Attendance Monitoring System</span>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="font-mono text-xl font-black text-black sm:text-3xl" x-text="currentTime"></div>
                <div class="text-[10px] font-bold text-slate-800 uppercase tracking-widest">{{ now()->format('l, F j') }}</div>
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
                        <video id="fb-video" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline muted></video>
                        <canvas id="fb-overlay" class="absolute inset-0 w-full h-full pointer-events-none scale-x-[-1]"></canvas>

                        {{-- Texture overlay --}}
                        <div class="absolute inset-0 pointer-events-none opacity-20 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

                        {{-- Targeting reticle --}}
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div :class="qualityOk ? 'border-green-500 scale-105' : 'border-blue-500/30'"
                                 class="w-[80%] h-[80%] border-2 border-dashed rounded-[60px] transition-all duration-700"></div>
                        </div>

                        {{-- Processing progress bar --}}
                        <div x-show="processing" class="absolute bottom-0 left-0 w-full h-2 bg-blue-900">
                            <div class="h-full bg-blue-500" style="animation: fb-progress 1s linear infinite;"></div>
                        </div>
                    </div>
                </div>

                {{-- BOTTOM CONTROLS --}}
                <div class="mt-10 space-y-4">
                    {{-- Status Card --}}
                    <div :class="processing ? 'bg-blue-600' : (qualityOk ? 'bg-green-600' : 'bg-slate-800')"
                         class="flex items-center justify-center px-6 py-4 transition-all shadow-xl rounded-3xl">
                        <div class="flex items-center space-x-3">
                            <template x-if="processing">
                                <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="processing ? 'AUTHENTICATING…' : statusMsg" class="text-sm font-black tracking-widest text-white uppercase"></span>
                        </div>
                    </div>

                    {{-- Quality meter --}}
                    <div class="flex items-center justify-between px-2 text-[10px] font-bold tracking-widest uppercase text-slate-500">
                        <span>Quality: <span x-text="qualityLabel" :class="qualityOk ? 'text-green-400' : 'text-yellow-400'"></span></span>
                        <span class="text-slate-600" x-text="(new Date()).toLocaleDateString()"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUCCESS MODAL --}}
        <div x-show="modal === 'success'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/95 backdrop-blur-xl">
            <div class="bg-white rounded-[50px] shadow-2xl max-w-md w-full p-12 text-center border-[12px] border-green-500 relative overflow-hidden">
                <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-green-600 bg-green-100 rounded-full shadow-inner">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-4xl font-black tracking-tighter uppercase text-slate-900">Verified</h3>

                <div class="mt-6 mb-8 space-y-4">
                    <p class="text-2xl font-black text-blue-600 uppercase" x-text="successName"></p>
                    <div class="inline-block px-6 py-2 text-sm font-black tracking-widest text-blue-700 uppercase border border-blue-100 rounded-full bg-blue-50"
                         x-text="phaseRecorded"></div>
                    <div class="font-mono text-2xl font-bold text-slate-400" x-text="successTime"></div>
                </div>

                <button @click="closeModal()"
                        class="w-full py-3 text-sm font-black tracking-widest text-white uppercase bg-green-600 hover:bg-green-500 rounded-full transition">
                    Done
                </button>
                <div class="absolute bottom-0 left-0 w-full h-3 bg-green-500"></div>
            </div>
        </div>

        {{-- FAIL MODAL --}}
        <div x-show="modal === 'fail'"
             x-transition:enter="transition ease-out duration-300"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-red-950/95 backdrop-blur-xl">
            <div class="bg-white rounded-[50px] shadow-2xl max-w-sm w-full p-12 text-center border-[12px] border-red-500 relative overflow-hidden">
                <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-red-600 bg-red-100 rounded-full">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h3 class="text-3xl font-black tracking-tighter uppercase text-slate-900">Scan Failed</h3>
                <p class="mt-2 mb-8 font-bold tracking-widest uppercase text-slate-500" x-text="failMsg"></p>
                <button @click="closeModal()"
                        class="w-full py-3 text-sm font-black tracking-widest text-white uppercase bg-red-600 hover:bg-red-500 rounded-full transition">
                    Try Again
                </button>
                <div class="absolute bottom-0 left-0 w-full h-3 bg-red-500"></div>
            </div>
        </div>

        {{-- DUPLICATE MODAL --}}
        <div x-show="modal === 'duplicate'"
             x-transition:enter="transition ease-out duration-300"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-yellow-950/95 backdrop-blur-xl">
            <div class="bg-white rounded-[50px] shadow-2xl max-w-sm w-full p-12 text-center border-[12px] border-yellow-500 relative overflow-hidden">
                <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-yellow-600 bg-yellow-100 rounded-full">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h3 class="text-3xl font-black tracking-tighter uppercase text-slate-900">Already Logged</h3>
                <p class="mt-2 text-lg font-bold uppercase text-blue-600" x-text="successName"></p>
                <p class="mt-1 mb-8 text-xs font-bold tracking-widest uppercase text-slate-500">All four clock-ins recorded today</p>
                <button @click="closeModal()"
                        class="w-full py-3 text-sm font-black tracking-widest text-white uppercase bg-yellow-600 hover:bg-yellow-500 rounded-full transition">
                    OK
                </button>
                <div class="absolute bottom-0 left-0 w-full h-3 bg-yellow-500"></div>
            </div>
        </div>

        {{-- SPOOF MODAL --}}
        <div x-show="modal === 'spoof'"
             x-transition:enter="transition ease-out duration-300"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-orange-950/95 backdrop-blur-xl">
            <div class="bg-white rounded-[50px] shadow-2xl max-w-sm w-full p-12 text-center border-[12px] border-orange-500 relative overflow-hidden">
                <div class="flex items-center justify-center w-24 h-24 mx-auto mb-8 text-orange-600 bg-orange-100 rounded-full">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h3 class="text-3xl font-black tracking-tighter uppercase text-slate-900">Try Again</h3>
                <p class="mt-2 mb-8 font-bold tracking-widest uppercase text-slate-500">Look directly at the camera in good lighting</p>
                <button @click="closeModal()"
                        class="w-full py-3 text-sm font-black tracking-widest text-white uppercase bg-orange-600 hover:bg-orange-500 rounded-full transition">
                    Try Again
                </button>
                <div class="absolute bottom-0 left-0 w-full h-3 bg-orange-500"></div>
            </div>
        </div>

    </div>{{-- end wire:ignore --}}

    <audio id="fb-success-sound"   src="{{ asset('audio/success-chime.mp3') }}" preload="auto"></audio>
    <audio id="fb-try-again-sound" src="{{ asset('audio/try-again.mp3') }}"   preload="auto"></audio>
    <audio id="fb-fail-sound"      src="{{ asset('audio/fail.mp3') }}"        preload="auto"></audio>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('faceBiometricsClock', (cfg) => ({
        cfg,
        currentTime: '',
        videoEl: null,
        canvasEl: null,
        overlayCtx: null,
        modelsLoaded: false,
        processing: false,
        modal: null,
        successName: '',
        successNumber: '',
        successTime: '',
        phaseRecorded: '',
        failMsg: '',
        statusMsg: 'INITIALIZING…',
        qualityLabel: '—',
        qualityOk: false,
        qualityOkSince: null,
        modalAutoCloseTimer: null,

        async init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            await this.loadModels();
            await this.startCamera();
            this.runDetectionLoop();
        },

        updateClock() {
            this.currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        async loadModels() {
            this.statusMsg = 'LOADING MODELS…';
            await faceapi.nets.tinyFaceDetector.loadFromUri(cfg.modelUrl);
            await faceapi.nets.faceLandmark68Net.loadFromUri(cfg.modelUrl);
            this.modelsLoaded = true;
            this.statusMsg = 'READY';
        },

        async startCamera() {
            this.videoEl    = document.getElementById('fb-video');
            this.canvasEl   = document.getElementById('fb-overlay');
            this.overlayCtx = this.canvasEl.getContext('2d');
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 720, height: 720, facingMode: 'user' } });
                this.videoEl.srcObject = stream;
                await new Promise(r => this.videoEl.addEventListener('loadedmetadata', r));
                this.canvasEl.width  = this.videoEl.videoWidth;
                this.canvasEl.height = this.videoEl.videoHeight;
            } catch (err) {
                this.statusMsg = err.name === 'NotAllowedError' ? 'CAMERA DENIED' : 'CAMERA ERROR';
            }
        },

        async runDetectionLoop() {
            if (!this.modelsLoaded || this.processing) {
                requestAnimationFrame(() => this.runDetectionLoop());
                return;
            }

            const detections = await faceapi
                .detectAllFaces(this.videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 416 }))
                .withFaceLandmarks();

            this.overlayCtx.clearRect(0, 0, this.canvasEl.width, this.canvasEl.height);

            if (detections.length === 0) {
                this.statusMsg      = 'AWAITING SUBJECT';
                this.qualityOk      = false;
                this.qualityLabel   = '—';
                this.qualityOkSince = null;
                requestAnimationFrame(() => this.runDetectionLoop());
                return;
            }

            // Pick the largest bbox = nearest face. Distant faces are ignored.
            const detection = detections.reduce((biggest, d) => {
                const a = d.detection.box.width * d.detection.box.height;
                const b = biggest.detection.box.width * biggest.detection.box.height;
                return a > b ? d : biggest;
            });

            // Faintly outline the ignored faces so the user knows they were seen but skipped
            for (const other of detections) {
                if (other === detection) {
                    continue;
                }
                const ob = other.detection.box;
                this.overlayCtx.strokeStyle = 'rgba(148, 163, 184, 0.5)';
                this.overlayCtx.lineWidth   = 1;
                this.overlayCtx.strokeRect(ob.x, ob.y, ob.width, ob.height);
            }

            const box = detection.detection.box;
            const faceHeightRatio   = box.height / this.canvasEl.height;
            const centerOffsetRatio = Math.abs(((box.x + box.width / 2) / this.canvasEl.width) - 0.5);

            this.qualityOk    = faceHeightRatio >= 0.35 && centerOffsetRatio <= 0.15;
            this.qualityLabel = this.qualityOk ? 'GOOD' : 'ADJUST';

            this.overlayCtx.strokeStyle = this.qualityOk ? '#22c55e' : '#eab308';
            this.overlayCtx.lineWidth   = 3;
            this.overlayCtx.strokeRect(box.x, box.y, box.width, box.height);

            if (this.qualityOk && !this.processing && this.modal === null) {
                if (this.qualityOkSince === null) {
                    this.qualityOkSince = Date.now();
                } else if (Date.now() - this.qualityOkSince >= 600) {
                    this.capture();
                }
            } else if (!this.qualityOk) {
                this.qualityOkSince = null;
            }

            this.statusMsg = this.qualityOk
                ? 'HOLD STILL…'
                : (faceHeightRatio < 0.35 ? 'MOVE CLOSER' : 'CENTER FACE');

            requestAnimationFrame(() => this.runDetectionLoop());
        },

        capture() {
            this.processing = true;
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 720;
            canvas.getContext('2d').drawImage(this.videoEl, 0, 0, 720, 720);

            const wire = Livewire.find(cfg.wireId);
            wire.verifyAndRecord(canvas.toDataURL('image/jpeg', 0.85)).then(() => {
                const type = wire.get('modalType');
                if (type === 'success') {
                    this.successName   = wire.get('employeeName')   ?? '';
                    this.successNumber = wire.get('employeeNumber') ?? '';
                    this.successTime   = wire.get('clockedTime')    ?? '';
                    this.phaseRecorded = wire.get('phaseRecorded')  ?? '';
                    document.getElementById('fb-success-sound').play().catch(() => {});
                } else if (type === 'fail') {
                    this.failMsg = wire.get('failReason') ?? 'Verification failed. Please try again.';
                    document.getElementById('fb-try-again-sound').play().catch(() => {});
                } else if (type === 'duplicate') {
                    this.successName = wire.get('employeeName') ?? '';
                    document.getElementById('fb-fail-sound').play().catch(() => {});
                } else if (type === 'spoof') {
                    document.getElementById('fb-try-again-sound').play().catch(() => {});
                }
                this.processing = false;
                this.modal = type;

                if (this.modalAutoCloseTimer) {
                    clearTimeout(this.modalAutoCloseTimer);
                }
                if (type) {
                    this.modalAutoCloseTimer = setTimeout(() => this.closeModal(), 3000);
                }
            });
        },

        closeModal() {
            if (this.modalAutoCloseTimer) {
                clearTimeout(this.modalAutoCloseTimer);
                this.modalAutoCloseTimer = null;
            }
            this.modal          = null;
            this.processing     = false;
            this.qualityOkSince = null;
            Livewire.find(cfg.wireId).closeModal();
        },
    }));
});
</script>
@endpush
