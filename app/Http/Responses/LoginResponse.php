<?php

namespace App\Http\Responses;

// use App\Filament\Resources\OrderResource;
use Auth;
use Filament\Facades\Filament;
use App\Filament\Pages\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use JoaoPauloLND\FilamentEditProfile\Filament\Pages\EditProfile;


class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {

        $user = auth()->user();

        if (
            is_null($user->name) ||
            is_null($user->division_id) ||
            is_null($user->mobile_number) ||
            is_null($user->address)
        ) {
            return redirect('/edit-profile');
        } else {
            return redirect()->intended(Filament::getUrl());
        }

        // return redirect()->intended(Filament::getUrl());
    }
}
