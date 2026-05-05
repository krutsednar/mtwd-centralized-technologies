<?php

namespace App\Filament\Gsms\Pages;

use Filament\Pages\Page;

class OfficialReceipts extends Page
{
    protected static ?string $navigationIcon = 'tabler-receipt';

    protected static string $view = 'filament.gsms.pages.official-receipts';

    protected static ?string $navigationGroup = 'Tables and Exports';
}
