<?php

namespace App\Livewire\Home;

use App\Models\LeaveCard;
use App\Models\Profile;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveCardTable extends Component
{
    use WithPagination;

    public string $category = '';
    public string $year     = '';

    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingYear(): void     { $this->resetPage(); }

    public function render(): \Illuminate\View\View
    {
        $profile = Profile::where('employee_number', auth()->user()->employee_number)->first();

        $entries   = collect();
        $vlBalance = 0;
        $slBalance = 0;

        if ($profile) {
            $query = LeaveCard::where('profile_id', $profile->id);

            if ($this->category) {
                $query->where('category', $this->category);
            }

            if ($this->year) {
                $query->whereYear('date_applied', $this->year);
            }

            $entries = $query->orderBy('date_applied')->paginate(20);

            // Running balances from ALL entries (not just filtered)
            $all = LeaveCard::where('profile_id', $profile->id)
                ->orderBy('date_applied')
                ->get();

            foreach ($all as $entry) {
                $vlBalance += (float) $entry->vl_earned - (float) $entry->vl_with_pay - (float) $entry->vl_without_pay;
                $slBalance += (float) $entry->sl_earned - (float) $entry->sl_with_pay - (float) $entry->sl_without_pay;
            }
        }

        $categories = LeaveCard::CATEGORY_SELECT;

        $years = collect(range(now()->year - 10, now()->year))
            ->mapWithKeys(fn ($y) => [$y => $y])
            ->reverse();

        return view('livewire.home.leave-card-table', compact(
            'profile', 'entries', 'vlBalance', 'slBalance', 'categories', 'years'
        ));
    }
}
