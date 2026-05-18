<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyPerformance extends Page
{
    protected static ?string $navigationIcon  = 'fas-user-check';
    protected static ?string $navigationLabel = 'Individual Performance';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.home.pages.my-performance';

    public function getTitle(): string
    {
        return 'Individual Performance';
    }
}
