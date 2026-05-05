<?php

namespace App\Filament\Home\Pages;

use App\Models\Profile;
use Filament\Pages\Page;

use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class MyProfile extends Page implements HasInfolists
{
    protected static ?string $navigationIcon = 'fas-id-card';

    protected static string $view = 'filament.home.pages.my-profile';

    public $profile;

    // public function mount(): void
    // {
    //     $this->profile = Profile::where('employee_number', auth()->user()->employee_number)->get();
    // }


    public function profileInfolist(Infolist $infolist): Infolist
    {
        $record = Profile::where('employee_number', auth()->user()->employee_number)->first();

        if (!$record) {
            $record = new Profile(['employee_number' => 'NO RECORD']);
        }

        return $infolist
            ->record($record)

            ->schema([
                Split::make([
                    Section::make('Profile Information')
                        ->columns([
                            'sm' => 2,
                            'xl' => 3,
                            '2xl' => 5,
                        ])
                        // ->description('Prevent abuse by limiting the number of requests per period')
                        ->schema([
                            TextEntry::make('employee_number'),
                            TextEntry::make('full_name')
                                ->columnSpan(2),
                            TextEntry::make('present_address')
                            ->columnSpan(2),
                            TextEntry::make('date_of_birth')
                                ->date('F d, Y'),
                            TextEntry::make('place_of_birth'),
                            TextEntry::make('sex'),
                            TextEntry::make('email')
                                ->icon('heroicon-m-envelope'),
                            TextEntry::make('mobile_number')
                            ->prefix('+63'),

                            TextEntry::make('gsis_id_no')
                                ->label('GSIS No.'),
                            TextEntry::make('pagibig_id_no')
                                ->label('PAGIBIG No.'),
                            TextEntry::make('philhealth_no')
                                ->label('Philhealth No.'),
                            TextEntry::make('tin_no')
                                ->label('TIN No.'),
                            TextEntry::make('sss_no')
                                ->label('SSS No.'),
                        ]),
                    Section::make([
                        TextEntry::make('status')
                            ->label('Employment Status'),
                        TextEntry::make('division.name'),
                    ])->grow(false),

                ])->from('md'),
            ]);
    }
}
