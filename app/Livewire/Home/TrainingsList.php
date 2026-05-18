<?php

namespace App\Livewire\Home;

use App\Models\Profile;
use App\Models\Training;
use Livewire\Component;
use Livewire\WithPagination;

class TrainingsList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $ldType = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingLdType(): void { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $profile = Profile::where('employee_number', auth()->user()->employee_number)->first();

        $trainings = collect();
        if ($profile) {
            $query = Training::where('profile_id', $profile->id);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('title', 'ilike', "%{$this->search}%")
                      ->orWhere('conducted_by', 'ilike', "%{$this->search}%");
                });
            }

            if ($this->ldType) {
                $query->where('ld_type', $this->ldType);
            }

            $trainings = $query->orderByDesc('from')->paginate(15);
        }

        $ldTypes  = Training::LD_TYPE_SELECT;
        $totalHrs = $profile ? Training::where('profile_id', $profile->id)->sum('number_of_hours') : 0;

        return view('livewire.home.trainings-list', compact('profile', 'trainings', 'ldTypes', 'totalHrs'));
    }
}
