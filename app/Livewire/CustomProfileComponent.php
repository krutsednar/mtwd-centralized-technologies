<?php

namespace App\Livewire;

use Filament\Forms;
use Livewire\Component;
use App\Models\Division;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;

class CustomProfileComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use HasSort;

    public string $userClass;
    public $user;

    public ?array $data = [];

    protected static int $sort = 0;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->userClass = get_class($this->user);
        $this->form->fill([
        'name' => $this->user->name,
        'email' => $this->user->email,
        'mobile_number' => $this->user->mobile_number,
        'address' => $this->user->address,
        'division_id' => $this->user->division_id,
        'avatar_url' => $this->user->avatar_url,
    ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->aside()
                    ->description('Update your division, mobile number and address.')
                    ->schema([
                        // FileUpload::make(config('filament-edit-profile.avatar_column', 'avatar_url'))
                        //     ->label(__('filament-edit-profile::default.avatar'))
                        //     ->avatar()
                        //     ->imageEditor()
                        //     ->disk(config('filament-edit-profile.disk', 'public'))
                        //     ->visibility(config('filament-edit-profile.visibility', 'public'))
                        //     ->directory(filament('filament-edit-profile')->getAvatarDirectory())
                        //     ->rules(filament('filament-edit-profile')->getAvatarRules())
                        //     ->hidden(! filament('filament-edit-profile')->getShouldShowAvatarForm()),
                        // TextInput::make('name')
                        //     ->label(__('filament-edit-profile::default.name'))
                        //     ->required(),
                        // TextInput::make('email')
                        //     ->label(__('filament-edit-profile::default.email'))
                        //     ->email()
                        //     ->required()
                        //     ->hidden(! filament('filament-edit-profile')->getShouldShowEmailForm())
                        //     ->unique($this->userClass, ignorable: $this->user),
                        Select::make('division_id')
                            ->label('Division')
                            ->required()
                            ->options(Division::all()->pluck('name', 'id')),
                        TextInput::make('mobile_number')
                            ->label('Mobile Number')
                            ->prefix('+63')
                            ->maxLength(10)
                            ->required(),
                        TextInput::make('address')
                            ->label('Address')
                            ->required(),

                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->user->update([
            // 'name' => $data['name'],
            // 'email' => $data['email'],
            'mobile_number' => $data['mobile_number'],
            'address' => $data['address'],
            'division_id' => $data['division_id'],
            // 'avatar_url' => $data['avatar_url'] ?? $this->user->avatar_url,
        ]);

        Notification::make()
            ->title('Your basic information has been saved successfully.')
            ->success()
            ->send();
        }

    public function render(): View
    {
        return view('livewire.custom-profile-component');
    }
}
