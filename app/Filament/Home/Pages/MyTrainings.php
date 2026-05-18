<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyTrainings extends Page
{
    protected static ?string $navigationIcon  = 'fas-certificate';
    protected static ?string $navigationLabel = 'My Trainings';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.home.pages.my-trainings';

    public function getTitle(): string
    {
        return 'Trainings & Development';
    }
}
