<?php

namespace App\Livewire\Gsms\InsurancePolicies;

use App\Models\VehicleInsurancePolicy;
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

class VehicleInsurances extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public static function table(Table $table): Table
    {
        return $table
            ->query(VehicleInsurancePolicy::query())
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Vehicle Policies Export - '.date('F d, Y')),
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
                Tables\Columns\TextColumn::make('policy_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('policy_expiration')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('policy_file')
                    ->label('Attachment')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('policy_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->policy_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Insurance Policy'),
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
        return view('livewire.gsms.insurance-policies.vehicle-insurances');
    }
}
