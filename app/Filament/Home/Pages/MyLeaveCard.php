<?php

namespace App\Filament\Home\Pages;

use Filament\Pages\Page;

class MyLeaveCard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Leave Card';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?int    $navigationSort  = 6;
    protected static string  $view            = 'filament.home.pages.my-leave-card';

    public function getTitle(): string
    {
        return 'Leave Card';
    }
}
