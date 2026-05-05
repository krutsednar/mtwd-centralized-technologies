<?php

namespace App\Livewire\Gsms\TaxDocuments;

use App\Models\RealPropertyTax;
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

class RealPropertyTaxes extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public static function table(Table $table): Table
    {
        return $table
            ->query(RealPropertyTax::query())
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Real Property Taxes Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('landStructure.property_name')
                    ->label('Property Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('or_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_issued')
                    ->date('F d, Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('attachment')
                    ->label('Attachment')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('attachment')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->attachment)),
                    )
                    ->alignCenter()
                    ->tooltip('View Attachment'),
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
        return view('livewire.gsms.tax-documents.real-property-taxes');
    }
}
