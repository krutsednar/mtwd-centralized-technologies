<?php

namespace App\Filament\Hris\Widgets;

use App\Models\LeaveCard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class LeaveCardStats extends BaseWidget
{
    public ?int $profileId = null;

    #[On('leaveCardProfileSelected')]
    public function updateProfile(int $profileId): void
    {
        $this->profileId = $profileId;
    }

    protected function getStats(): array
    {
        if (! $this->profileId) {
            return [
                Stat::make('Vacation Leave Credits', '—')->color('gray'),
                Stat::make('Sick Leave Credits', '—')->color('gray'),
                Stat::make('Total Available Leave Credits', '—')->color('gray'),
            ];
        }

        $query = LeaveCard::query()->where('profile_id', $this->profileId);

        $vlEarned     = (float) (clone $query)->sum('vl_earned');
        $vlWithPay    = (float) (clone $query)->sum('vl_with_pay');
        $slEarned     = (float) (clone $query)->sum('sl_earned');
        $slWithPay    = (float) (clone $query)->sum('sl_with_pay');

        $vlCredits    = $vlEarned - $vlWithPay;
        $slCredits    = $slEarned - $slWithPay;
        $totalCredits = $vlCredits + $slCredits;

        return [
            Stat::make('Vacation Leave Credits', number_format($vlCredits, 3))
                ->description('VL Earned: ' . number_format($vlEarned, 3) . ' | Used: ' . number_format($vlWithPay, 3))
                ->color($vlCredits >= 0 ? 'success' : 'danger'),

            Stat::make('Sick Leave Credits', number_format($slCredits, 3))
                ->description('SL Earned: ' . number_format($slEarned, 3) . ' | Used: ' . number_format($slWithPay, 3))
                ->color($slCredits >= 0 ? 'info' : 'danger'),

            Stat::make('Total Available Leave Credits', number_format($totalCredits, 3))
                ->description('VL + SL balance')
                ->color($totalCredits >= 0 ? 'warning' : 'danger'),
        ];
    }
}
