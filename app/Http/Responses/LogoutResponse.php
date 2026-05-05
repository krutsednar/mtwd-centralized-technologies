<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Responsable;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        return Redirect::to('/login');
    }
}
