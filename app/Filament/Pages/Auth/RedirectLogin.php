<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class RedirectLogin extends BaseLogin
{
    public function mount(): void
    {
        // redirect to the login page of the 'home' panel
        redirect()->route('filament.home.auth.login');
    }
}
