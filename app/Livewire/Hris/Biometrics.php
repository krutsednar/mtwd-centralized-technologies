<?php

namespace App\Livewire\Hris;

use App\Models\Attendance;
use App\Models\Profile;
use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Biometrics extends Component
{

    public $view = 'scan'; // scan, enroll, or manual
    public $message = "Position your face to clock in/out";
    public $status = "info";
    public $selectedProfileId;

    // --- 1. ENROLLMENT (Registering the face) ---
    public function enrollFace($base64)
    {
        if (!$this->selectedProfileId) return $this->error("Select a Profile first.");
        $profile = Profile::find($this->selectedProfileId);

        $response = $this->sendToCompreFace($base64, "faces?subject=" . urlencode($profile->name));

        if ($response->successful()) {
            $profile->update(['face_enrolled' => true]);
            $this->success("Biometrics registered for {$profile->name}!");
            $this->view = 'scan';
        }
    }

    // --- 2. RECOGNITION (Daily Clock-In) ---
    public function processScan($base64)
    {
        $response = $this->sendToCompreFace($base64, "recognize");
        $subject = $response->json()['result'][0]['subjects'][0] ?? null;

        if ($subject && $subject['similarity'] > 0.90) {
            $profile = Profile::where('name', $subject['subject'])->first();
            return $this->recordAttendance($profile->id);
        }
        $this->error("Face not recognized.");
    }

    // --- 3. ATTENDANCE LOGIC ---
    public function recordAttendance($profileId, $manual = false)
    {
        $record = Attendance::firstOrCreate([
            'profile_id' => $profileId,
            'date' => now()->toDateString()
        ]);

        $now = now();
        $h = $now->hour;

        // Smart Slotting logic based on time
        if (!$record->morning_in) $record->update(['morning_in' => $now]);
        elseif (!$record->lunch_out && $h >= 11 && $h < 13) $record->update(['lunch_out' => $now]);
        elseif (!$record->lunch_in && $h >= 12 && $h < 15) $record->update(['lunch_in' => $now]);
        elseif (!$record->afternoon_out && $h >= 16 && $h < 18) $record->update(['afternoon_out' => $now]);
        else $record->update(['overtime_out' => $now]);

        $this->success("Attendance recorded!" . ($manual ? " (Manual)" : ""));
        $this->view = 'scan';
    }

    private function sendToCompreFace($base64, $endpoint)
    {
        $data = base64_decode(explode(',', $base64)[1]);
        return Http::withHeaders(['x-api-key' => env('COMPREFACE_KEY')])
            ->attach('file', $data, 'image.jpg')
            ->post(env('COMPREFACE_URL') . "/api/v1/recognition/" . $endpoint);
    }

    private function success($msg) { $this->status = 'success'; $this->message = $msg; }
    private function error($msg) { $this->status = 'error'; $this->message = $msg; }

    public function render()
    {
        return view('livewire.hris.biometrics', [
            'enrolledProfiles' => Profile::where('face_enrolled', true)->get(),
            'unregisteredProfiles' => Profile::where('face_enrolled', false)->get()
        ]);
    }
}
