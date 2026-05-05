<?php

namespace App\Filament\Gsms\Pages;

use Filament\Pages\Page;

class TaxDocuments extends Page
{
    protected static ?string $navigationIcon = 'tabler-receipt-tax';

    protected static string $view = 'filament.gsms.pages.tax-documents';

    protected static ?string $navigationGroup = 'Tables and Exports';
}
