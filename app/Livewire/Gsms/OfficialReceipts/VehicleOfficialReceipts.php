<?php

namespace App\Livewire\Gsms\OfficialReceipts;

use App\Models\VehicleOfficialReceipt;
use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class VehicleOfficialReceipts extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public static function table(Table $table): Table
    {
        return $table
            ->query(VehicleOfficialReceipt::query())
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Vehicle ORs Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.vehicleType.name')
                    ->label('Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.brand')
                    ->label('Brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.model')
                    ->label('Model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('or_no')
                    ->label('OR No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('or_expiration')
                    ->label('OR Expiration')
                    ->date('F d, Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('or_file')
                    ->label('OR File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('or_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->or_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Official Receipt'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function render()
    {
        return view('livewire.gsms.official-receipts.vehicle-official-receipts');
    }
}
