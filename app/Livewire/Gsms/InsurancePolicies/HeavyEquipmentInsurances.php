<?php

namespace App\Livewire\Gsms\InsurancePolicies;

use App\Models\HeavyEquipmentInsurancePolicy;
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


class HeavyEquipmentInsurances extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HeavyEquipmentInsurancePolicy::query())
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Heavy Equipment Policies Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('heavyEquipment.heavyEquipmentType.name')
                    ->label('Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('heavyEquipment.brand')
                    ->label('Brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('heavyEquipment.model')
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
        return view('livewire.gsms.insurance-policies.heavy-equipment-insurances');
    }
}
