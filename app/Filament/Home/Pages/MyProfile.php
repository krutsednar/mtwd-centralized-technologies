<?php

namespace App\Filament\Home\Pages;

use App\Models\Profile;
use Carbon\Carbon;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;

class MyProfile extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'fas-id-card';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.home.pages.my-profile';

    public ?Profile $profile = null;

    public function getTitle(): string
    {
        return 'Employee Profile';
    }

    public function mount(): void
    {
        $this->profile = Profile::forCurrentUser()?->load([
            'division', 'children', 'skills', 'organizations',
            'educationalBackgrounds', 'eligibilities', 'workExperiences',
        ]);
    }

    public function profileInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->profile)
            ->schema([
                Section::make()
                    ->schema([
                        ImageEntry::make('picture')
                            ->hiddenLabel()
                            ->disk('public')
                            ->circular()
                            ->height(88)
                            ->width(88),
                        TextEntry::make('full_name')
                            ->hiddenLabel()
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('employee_number')
                            ->hiddenLabel()
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('division.name')
                            ->hiddenLabel()
                            ->badge()
                            ->color('info')
                            ->placeholder('No division'),
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('—')
                            ->color(fn (?string $state): string => match (true) {
                                $state && str_contains($state, 'Active') => 'success',
                                $state && (str_contains($state, 'Inactive') || str_contains($state, 'Resigned')) => 'danger',
                                default => 'primary',
                            }),
                    ])
                    ->columns(['default' => 1, 'sm' => 5]),

                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Primary Info')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Personal Information')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('date_of_birth')->date('F d, Y')->placeholder('—'),
                                        TextEntry::make('place_of_birth')->placeholder('—'),
                                        TextEntry::make('sex')->formatStateUsing(fn (?string $state): string => match ($state) {
                                            'M' => 'Male', 'F' => 'Female', default => $state ?? '—',
                                        }),
                                        TextEntry::make('citizenship')->placeholder('—'),
                                        TextEntry::make('email')->placeholder('—')->copyable(),
                                        TextEntry::make('mobile_number')->prefix('+63 ')->placeholder('—'),
                                        TextEntry::make('present_address')->placeholder('—')->columnSpanFull(),
                                    ]),

                                Section::make('Government IDs')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('gsis_id_no')->label('GSIS No.')->placeholder('—')->copyable(),
                                        TextEntry::make('pagibig_id_no')->label('Pag-IBIG No.')->placeholder('—')->copyable(),
                                        TextEntry::make('philhealth_no')->label('PhilHealth No.')->placeholder('—')->copyable(),
                                        TextEntry::make('sss_no')->label('SSS No.')->placeholder('—')->copyable(),
                                        TextEntry::make('tin_no')->label('TIN No.')->placeholder('—')->copyable(),
                                    ]),

                                Section::make('Family Background')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('spouse')
                                            ->state(fn (Profile $r): ?string => static::nameOf($r->spouse_first_name, $r->spouse_middle_name, $r->spouse_surname))
                                            ->placeholder('Not provided'),
                                        TextEntry::make('father')
                                            ->state(fn (Profile $r): ?string => static::nameOf($r->father_first_name, $r->father_middle_name, $r->father_surname))
                                            ->placeholder('Not provided'),
                                        TextEntry::make('mother')
                                            ->label("Mother's Maiden Name")
                                            ->state(fn (Profile $r): ?string => static::nameOf($r->mother_first_name, $r->mother_middle_name, $r->mother_surname))
                                            ->placeholder('Not provided'),
                                    ]),

                                Section::make('Children')
                                    ->visible(fn (Profile $r): bool => $r->children->isNotEmpty())
                                    ->schema([
                                        RepeatableEntry::make('children')
                                            ->hiddenLabel()
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('name')->weight(FontWeight::Medium)->placeholder('—'),
                                                TextEntry::make('date_of_birth')->date('F d, Y')->placeholder('—'),
                                            ]),
                                    ]),

                                Section::make('Special Skills / Hobbies')
                                    ->visible(fn (Profile $r): bool => $r->skills->isNotEmpty())
                                    ->schema([
                                        TextEntry::make('skills.name')->hiddenLabel()->badge()->color('info'),
                                    ]),

                                Section::make('Membership in Associations / Organizations')
                                    ->visible(fn (Profile $r): bool => $r->organizations->isNotEmpty())
                                    ->schema([
                                        RepeatableEntry::make('organizations')
                                            ->hiddenLabel()
                                            ->columns(2)
                                            ->schema([
                                                TextEntry::make('organization_name')->weight(FontWeight::Medium)->placeholder('—'),
                                                TextEntry::make('position_title')->placeholder('—'),
                                                TextEntry::make('organization_address')->placeholder('—')->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Education')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                RepeatableEntry::make('educationalBackgrounds')
                                    ->hiddenLabel()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('school_name')->weight(FontWeight::Medium)->columnSpan(2),
                                        TextEntry::make('level')->badge()->placeholder('—'),
                                        TextEntry::make('degree_course')->placeholder('—')->columnSpan(2),
                                        TextEntry::make('period')
                                            ->state(fn ($record): string => static::yearRange($record->from, $record->to)),
                                        TextEntry::make('year_graduated')->placeholder('—'),
                                        TextEntry::make('highest_grade')->placeholder('—'),
                                        TextEntry::make('honors')->placeholder('—'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Eligibility')
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                RepeatableEntry::make('eligibilities')
                                    ->hiddenLabel()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('eligibility')->weight(FontWeight::Medium)->columnSpan(2),
                                        TextEntry::make('rating')->placeholder('—'),
                                        TextEntry::make('date_of_examination')->date('M d, Y')->placeholder('—'),
                                        TextEntry::make('place_of_examination')->placeholder('—'),
                                        TextEntry::make('license_no')->label('License No.')->placeholder('—'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Work Experience')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                RepeatableEntry::make('workExperiences')
                                    ->hiddenLabel()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('position_title')->weight(FontWeight::Medium)->columnSpan(2),
                                        TextEntry::make('period')
                                            ->state(fn ($record): string => static::dateRange($record->from, $record->to)),
                                        TextEntry::make('agency')->placeholder('—')->columnSpan(2),
                                        TextEntry::make('appointment_status')->badge()->placeholder('—'),
                                        TextEntry::make('monthly_salary')->money('PHP')->placeholder('—'),
                                        TextEntry::make('salary_grade')->label('SG')->placeholder('—'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function nameOf(?string $first, ?string $middle, ?string $surname): ?string
    {
        $name = trim(implode(' ', array_filter([$first, $middle, $surname])));

        return $name !== '' ? $name : null;
    }

    private static function yearRange($from, $to): string
    {
        return ($from ? Carbon::parse($from)->format('Y') : '—').' – '.($to ? Carbon::parse($to)->format('Y') : 'Present');
    }

    private static function dateRange($from, $to): string
    {
        return ($from ? Carbon::parse($from)->format('M Y') : '—').' – '.($to ? Carbon::parse($to)->format('M Y') : 'Present');
    }
}
