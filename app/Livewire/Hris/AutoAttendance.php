<?php

namespace App\Livewire\Hris;

use App\Models\Attendance;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AutoAttendance extends Component
{
    public $phase;
    public $employeeNumber;
    public $showSuccessModal = false;
    public $showFailModal = false;
    public $showDuplicateModal = false;
    public $currentEmployee = ['name' => 'Ready', 'image_url' => null];

    public function mount($phase = null)
    {
        $this->phase = $phase ?? $this->getAutomaticPhase();
    }

    public function getAutomaticPhase()
    {
        $hour = now()->hour;
        if ($hour < 10) return 'morning_in';
        if ($hour < 12) return 'morning_out';
        if ($hour < 15) return 'afternoon_in';
        return 'afternoon_out';
    }

    public function recordAttendance($photo)
    {
        if (!$photo) return;

        try {
            $imageParts = explode(";base64,", $photo);
            $imageDecoded = base64_decode($imageParts[1]);

            $response = Http::timeout(10)
                ->withHeaders(['x-api-key' => env('COMPREFACE_KEY')])
                ->attach('file', $imageDecoded, 'webcam.jpg')
                ->post(env('COMPREFACE_URL') . '/api/v1/recognition/recognize?min_detection_size=120&limit=2');

            if (!$response->successful()) {
                $this->triggerFail();
                return;
            }

            $data = $response->json();
            $result = $data['result'][0] ?? null;

            if (!$result || empty($result['subjects'])) {
                $this->triggerFail();
                return;
            }

            $firstMatch = $result['subjects'][0];
            $secondMatch = $result['subjects'][1] ?? null;

            // Hardened Validation
            if ($firstMatch['similarity'] < 0.96 || ($secondMatch && ($firstMatch['similarity'] - $secondMatch['similarity'] < 0.03))) {
                $this->triggerFail();
                return;
            }

            $this->employeeNumber = $firstMatch['subject'];

            $profile = Profile::where('employee_number', $this->employeeNumber)->first();

            $existing = Attendance::where('employee_number', $this->employeeNumber)
                ->where('attendance_date', now()->toDateString())
                ->whereNotNull($this->phase)
                ->first();

            if ($existing) {
                $this->showDuplicateModal = true;

                return;
            }

            $attendance = Attendance::updateOrCreate(
                ['employee_number' => $this->employeeNumber, 'attendance_date' => now()->toDateString()],
                ['profile_id' => $profile?->id, $this->phase => now()->toTimeString()]
            );

            $name = $profile?->full_name ?? 'Unknown';

            $this->currentEmployee = [
                'name' => $name,
                'image_url' => "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=0D8ABC&color=fff"
            ];

            $this->showSuccessModal = true;

        } catch (\Exception $e) {
            Log::error("Attendance System Error: " . $e->getMessage());
            $this->triggerFail();
        }
    }

    private function triggerFail()
    {
        $this->showFailModal = true;
    }

    public function render()
    {
        return view('livewire.hris.auto-attendance');
    }
}
