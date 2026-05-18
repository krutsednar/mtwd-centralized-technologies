<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyProfile extends Page
{
    protected static ?string $navigationIcon  = 'fas-id-card';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.home.pages.my-profile';

    public function getTitle(): string
    {
        return 'Employee Profile';
    }
}
