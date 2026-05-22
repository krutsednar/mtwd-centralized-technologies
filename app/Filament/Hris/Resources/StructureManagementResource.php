<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\StructureManagementResource\Pages;
use App\Models\Division;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class StructureManagementResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static ?string $slug = 'org-structure';

    protected static ?string $navigationGroup = 'Organization Management';

    protected static ?string $navigationLabel = 'Structure Management';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'Org Unit';

    protected static ?string $pluralModelLabel = 'Org Structure';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;   // creation handled from within the tree page
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrgStructure::route('/'),
        ];
    }
}
