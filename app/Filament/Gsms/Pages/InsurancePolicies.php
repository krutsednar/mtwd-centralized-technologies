<?php

namespace App\Filament\Gsms\Pages;

use Filament\Pages\Page;

class InsurancePolicies extends Page
{
    protected static ?string $navigationIcon = 'fas-file-lines';

    protected static string $view = 'filament.gsms.pages.insurance-policies';

    protected static ?string $navigationGroup = 'Tables and Exports';
}
