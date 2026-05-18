<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyServiceRecord extends Page
{
    protected static ?string $navigationIcon  = 'fas-list';
    protected static ?string $navigationLabel = 'Service Record';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.home.pages.my-service-record';

    public function getTitle(): string
    {
        return 'Service Record';
    }
}
