@push('head-scripts')
<script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
@endpush

{{-- Outer div is the Livewire root (server-rendered state lives here). --}}
<div class="min-h-screen flex flex-col items-center justify-center p-6">

    {{-- Header --}}
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-white">Face Enrollment — Webcam</h1>
        @if($employeeName)
            <p class="text-blue-400 mt-1">{{ $employeeName }}</p>
        @endif
        <p class="text-gray-400 text-sm mt-1">Capture {{ $totalFrames }} frames from different angles</p>
    </div>

    {{-- Progress bar (Livewire-managed) --}}
    <div class="w-full max-w-sm mb-4">
        <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span>Frames captured</span>
            <span>{{ $currentFrame }}/{{ $totalFrames }}</span>
        </div>
        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full transition-all"
                 style="width: {{ ($currentFrame / $totalFrames) * 100 }}%"></div>
        </div>
    </div>

    {{-- Pose instruction --}}
    @if($currentFrame < $totalFrames)
    <div class="text-center mb-4">
        <p class="text-white text-lg font-medium">{{ $poses[$currentFrame] }}</p>
    </div>
    @endif

    {{-- Completion / failure states --}}
    @if($enrollmentComplete)
    <div class="bg-green-800/50 border border-green-600 rounded-2xl p-8 text-center max-w-sm w-full">
        <div class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">Enrollment Complete!</h2>
        <p class="text-gray-300">All {{ $totalFrames }} frames enrolled successfully.</p>
        <button wire:click="resetEnrollment" class="mt-6 w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl">
            Enroll Another
        </button>
    </div>
    @elseif($enrollmentFailed)
    <div class="bg-red-800/50 border border-red-600 rounded-2xl p-6 text-center max-w-sm w-full">
        <h2 class="text-xl font-bold text-white mb-2">Enrollment Failed</h2>
        <p class="text-gray-300 text-sm">{{ $enrollmentError }}</p>
        <button wire:click="resetEnrollment" class="mt-4 w-full bg-red-600 hover:bg-red-500 text-white font-semibold py-3 rounded-xl">
            Try Again
        </button>
    </div>
    @else

    {{-- Camera section — Alpine-managed, Livewire leaves it alone --}}
    <div wire:ignore
         x-data="faceEnrollKiosk(@js([
             'modelUrl' => asset('vendor/face-api/weights'),
             'wireId'   => $this->getId(),
         ]))"
         x-init="init()">

        <div class="relative w-[320px] h-[320px] rounded-2xl overflow-hidden bg-black shadow-2xl">
            <video id="enroll-video" class="w-full h-full object-cover scale-x-[-1]" autoplay playsinline muted></video>
            <canvas id="enroll-overlay" class="absolute inset-0 w-full h-full pointer-events-none scale-x-[-1]"></canvas>
            <div class="absolute bottom-0 inset-x-0 bg-black/60 px-3 py-1 text-xs text-white text-center" x-text="statusMsg"></div>
        </div>

        @if(isset($frameErrors[$currentFrame]))
        <p class="text-red-400 text-sm mt-3 max-w-xs text-center">{{ $frameErrors[$currentFrame] }}</p>
        @endif

        <button @click="manualCapture()" :disabled="!qualityOk || capturing"
                class="mt-4 px-8 py-3 bg-blue-600 hover:bg-blue-500 disabled:bg-gray-600 text-white font-semibold rounded-xl transition">
            <span x-text="capturing ? 'Processing…' : 'Capture Frame'"></span>
        </button>

    </div>{{-- end wire:ignore --}}

    @endif

    <a href="{{ route('face-biometrics.index') }}" class="mt-6 text-gray-500 hover:text-gray-300 text-sm">← Back</a>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('faceEnrollKiosk', (cfg) => ({
        cfg,
        videoEl: null,
        canvasEl: null,
        modelsLoaded: false,
        capturing: false,
        qualityOk: false,
        statusMsg: 'Loading models…',

        async init() {
            await faceapi.nets.tinyFaceDetector.loadFromUri(cfg.modelUrl);
            await faceapi.nets.faceLandmark68Net.loadFromUri(cfg.modelUrl);
            this.modelsLoaded = true;
            this.statusMsg = 'Ready';

            this.videoEl  = document.getElementById('enroll-video');
            this.canvasEl = document.getElementById('enroll-overlay');

            const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 640 } });
            this.videoEl.srcObject = stream;
            await new Promise(r => this.videoEl.addEventListener('loadedmetadata', r));
            this.canvasEl.width  = this.videoEl.videoWidth;
            this.canvasEl.height = this.videoEl.videoHeight;

            this.detectLoop();
        },

        async detectLoop() {
            if (!this.modelsLoaded) { requestAnimationFrame(() => this.detectLoop()); return; }

            const dets = await faceapi
                .detectAllFaces(this.videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 320 }));

            const ctx = this.canvasEl.getContext('2d');
            ctx.clearRect(0, 0, this.canvasEl.width, this.canvasEl.height);

            if (dets.length === 0) {
                this.qualityOk = false;
                this.statusMsg = 'No face detected';
            } else {
                // Pick nearest face (largest bbox). Distant faces are ignored.
                const det = dets.reduce((biggest, d) =>
                    (d.box.width * d.box.height) > (biggest.box.width * biggest.box.height) ? d : biggest
                );

                // Outline ignored faces faintly
                for (const o of dets) {
                    if (o === det) continue;
                    ctx.strokeStyle = 'rgba(148, 163, 184, 0.5)';
                    ctx.lineWidth   = 1;
                    ctx.strokeRect(o.box.x, o.box.y, o.box.width, o.box.height);
                }

                const box = det.box;
                this.qualityOk = (box.height / this.canvasEl.height) >= 0.30;
                ctx.strokeStyle = this.qualityOk ? '#22c55e' : '#eab308';
                ctx.lineWidth   = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                this.statusMsg = this.qualityOk ? 'Face detected — ready to capture' : 'Move closer';
            }

            requestAnimationFrame(() => this.detectLoop());
        },

        manualCapture() {
            if (!this.qualityOk || this.capturing) return;
            this.capturing = true;
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 640;
            canvas.getContext('2d').drawImage(this.videoEl, 0, 0, 640, 640);
            Livewire.find(cfg.wireId).captureFrame(canvas.toDataURL('image/jpeg', 0.90))
                .then(() => { this.capturing = false; });
        },
    }));
});
</script>
@endpush
