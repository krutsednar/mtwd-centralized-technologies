<?php

namespace App\Shared\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

/**
 * Account-status header widget shown on every panel dashboard.
 *
 * Lives under App\Shared because it is referenced by Admin, Hris, Gsms, and
 * Home panel providers — it is not panel-specific. Renames the original
 * App\Filament\Home\Resources\HomeResource\Widgets\CustomAccountWidget.
 */
class AccountStatusWidget extends BaseAccountWidget
{
    protected static string $view = 'shared.filament.widgets.account-status-widget';
}
