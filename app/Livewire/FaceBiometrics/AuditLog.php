<?php

namespace App\Livewire\FaceBiometrics;

use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\Profile;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLog extends Component
{
    use WithPagination;

    public string $filterEvent = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterProfile = '';

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingFilterProfile(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = FaceAuditLog::with('profile')
            ->orderBy('created_at', 'desc');

        if ($this->filterEvent) {
            $query->where('event', $this->filterEvent);
        }

        if ($this->filterDateFrom) {
            $query->where('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->where('created_at', '<=', $this->filterDateTo.' 23:59:59');
        }

        if ($this->filterProfile) {
            $query->whereHas('profile', function ($q) {
                $q->where('employee_number', 'like', "%{$this->filterProfile}%")
                    ->orWhere('first_name', 'like', "%{$this->filterProfile}%")
                    ->orWhere('surname', 'like', "%{$this->filterProfile}%");
            });
        }

        $logs = $query->paginate(25);

        // Daily summary stats
        $today = today()->toDateString();
        $todayLogs = FaceAuditLog::whereDate('created_at', $today)->get();
        $totalToday = $todayLogs->count();
        $successToday = $todayLogs->whereIn('event', ['verify_ok', 'enroll', 'enroll_seed'])->count();
        $successRate = $totalToday > 0 ? round($successToday / $totalToday * 100, 1) : 0;
        $spoofCount = $todayLogs->where('event', 'spoof_detected')->count();

        $topFailureReasons = $todayLogs
            ->whereNotIn('event', ['verify_ok', 'enroll', 'enroll_seed'])
            ->groupBy('event')
            ->map->count()
            ->sortDesc()
            ->take(5);

        $topFailedProfiles = $todayLogs
            ->whereNotIn('event', ['verify_ok', 'enroll', 'enroll_seed'])
            ->whereNotNull('profile_id')
            ->groupBy('profile_id')
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->keys()
            ->map(fn ($id) => Profile::find($id));

        $events = [
            'enroll', 'enroll_seed', 'verify_ok', 'verify_fail',
            'spoof_detected', 'low_quality', 'no_face', 'multiple_faces', 'duplicate',
        ];

        return view('livewire.face-biometrics.audit-log', compact(
            'logs', 'events', 'totalToday', 'successRate', 'spoofCount',
            'topFailureReasons', 'topFailedProfiles'
        ))->layout('components.layouts.app');
    }
}
