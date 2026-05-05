<?php

namespace App\Livewire\Hris;

use Filament\Tables;
use App\Models\Profile;
use Livewire\Component;
use App\Models\Training;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class TrainingTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Training::query()
                    ->where('profile_id', Profile::where('employee_number', auth()->user()->employee_number)->value('id'))
                    ->orderBy('from', 'desc');
            })
            ->heading('My Trainings')
            ->columns([
                // Tables\Columns\TextColumn::make('profile.employee_number')
                //     ->label('Employee Number')
                //     ->sortable()
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('profile.full_name')
                //     ->label('Employee Name')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('from')
                    ->date('F d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('to')
                    ->date('F d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_hours')
                    ->label('No. of Hours')
                    ->numeric()
                    ->suffix(' hours')
                    ->sortable(),
                Tables\Columns\TextColumn::make('conducted_by')
                    ->searchable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ld_type')
                    ->searchable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('attachment')
                    ->label('Certificate')
                    ->trueIcon('fas-certificate')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('attachment')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->attachment)),
                    )
                    ->alignCenter()
                    ->tooltip('View Training Certificate'),
            ])
            ->filters([
                //
            ]);
    }

    public function render()
    {
        return view('livewire.hris.training-table');
    }
}
