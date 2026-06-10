<?php

namespace App\Livewire;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;
use Livewire\Component;

class CustomProfileComponent extends Component implements HasForms
{
    use HasSort;
    use InteractsWithForms;

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
            'avatar_url' => $this->user->avatar_url,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->aside()
                    ->description('Update your mobile number and address.')
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
