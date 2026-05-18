<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyDtr extends Page
{
    protected static ?string $navigationIcon  = 'fas-clock';
    protected static ?string $navigationLabel = 'My DTR';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.home.pages.my-dtr';

    public function getTitle(): string
    {
        return 'Daily Time Record';
    }
}
