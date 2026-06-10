<?php

namespace App\Filament\Hris\Pages;

use App\Models\Division;
use App\Models\HrSetting;
use App\Models\Profile;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/**
 * Central HR configuration. Holds the designated signatories used when resolving
 * the "Details of Action" sections of HR forms. Keys are namespaced by form
 * (leave.*, cto.*, ob_slip.*, pass_slip.*) so new forms add a Section here
 * without any structural change.
 */
class HrisConfiguration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'HRIS Configuration';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.hris.pages.hris-configuration';

    public ?array $data = [];

    /**
     * Settings keys edited on this page. Add future-form keys here and a matching
     * field in form() — the load/save loop is generic.
     *
     * @var list<string>
     */
    public const SETTING_KEYS = [
        'leave.designated_approver_profile_id',
        'leave.hr_leave_administrator_profile_id',
        'leave.hr_division_id',
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || collect($user->getRoleNames())
                ->contains(fn ($role) => Str::startsWith(Str::upper($role), 'HRIS'));
    }

    public function getTitle(): string
    {
        return 'HRIS Configuration';
    }

    public function mount(): void
    {
        $state = [];

        foreach (self::SETTING_KEYS as $key) {
            $state[$key] = HrSetting::get($key);
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Leave Application — Signatories')
                    ->description('Used to resolve sections 7.A and 7.C of the Application for Leave (CS Form No. 6).')
                    ->schema([
                        Select::make('leave.hr_leave_administrator_profile_id')
                            ->label('Designated HR Employee (manages leave)')
                            ->helperText('Signs 7.A — Certification of Leave Credits (first signatory).')
                            ->options(static::profileOptions())
                            ->searchable()
                            ->nullable(),

                        Select::make('leave.hr_division_id')
                            ->label('HR Division / Office')
                            ->helperText('Its active head (or OIC) signs 7.A as HR Division Chief (second signatory).')
                            ->options(static::divisionOptions())
                            ->searchable()
                            ->nullable(),

                        Select::make('leave.designated_approver_profile_id')
                            ->label('Designated Approving Signatory (7.C)')
                            ->helperText('Approves 7.C when the applicant is NOT a manager-level employee. Managerial applicants are routed to the General Manager automatically.')
                            ->options(static::profileOptions())
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::SETTING_KEYS as $key) {
            HrSetting::set($key, $data[$key] ?? null);
        }

        Notification::make()
            ->title('HRIS configuration saved.')
            ->success()
            ->send();
    }

    /**
     * @return array<int, string>
     */
    protected static function profileOptions(): array
    {
        return Profile::query()
            ->orderBy('surname')
            ->get()
            ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number.' — '.$p->full_name])
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    protected static function divisionOptions(): array
    {
        return Division::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }
}
