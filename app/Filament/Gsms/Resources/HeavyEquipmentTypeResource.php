<?php

namespace App\Filament\Gsms\Resources;

use App\Filament\Gsms\Resources\HeavyEquipmentTypeResource\Pages;
use App\Filament\Gsms\Resources\HeavyEquipmentTypeResource\RelationManagers;
use App\Models\HeavyEquipmentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HeavyEquipmentTypeResource extends Resource
{
    protected static ?string $model = HeavyEquipmentType::class;

    protected static ?string $navigationIcon = 'tabler-backhoe';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Heavy Equipment Types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeavyEquipmentTypes::route('/'),
            'create' => Pages\CreateHeavyEquipmentType::route('/create'),
            'edit' => Pages\EditHeavyEquipmentType::route('/{record}/edit'),
        ];
    }
}
