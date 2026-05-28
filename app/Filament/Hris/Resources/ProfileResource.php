<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\ProfileResource\Pages;
use App\Models\Award;
use App\Models\Division;
use App\Models\EducationalBackground;
use App\Models\Eligibility;
use App\Models\Profile;
use App\Models\Training;
use Asmit\FilamentUpload\Enums\PdfViewFit;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationIcon = 'fas-id-card';

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $navigationLabel = 'Employee Profiles';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    /** ================= PRIMARY INFO ================= */
                    Step::make('Primary Info')
                        ->afterValidation(function () {
                            // ...
                        })
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        ->columns([
                            'sm' => 3,
                            'xl' => 6,
                            '2xl' => 8,
                        ])
                        ->schema([
                            AdvancedFileUpload::make('picture')
                                ->label('Upload Employee Picture')
                                ->directory('employee_pictures')
                                ->pdfPreviewHeight(400)
                                ->pdfDisplayPage(1)
                                ->pdfToolbar(true)
                                ->pdfZoomLevel(100)
                                ->pdfFitType(PdfViewFit::FIT)
                                ->pdfNavPanes(true)
                                ->image()
                                ->imageEditor()
                                ->imageResizeTargetWidth('1080')
                                ->imageResizeTargetHeight('1080')

                                // ->avatar()
                                // ->circleCropper()
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            AdvancedFileUpload::make('pds')
                                ->label('Upload Personal Data Sheet (PDS)')
                                ->directory('pds_files')
                                ->pdfPreviewHeight(400)
                                ->pdfDisplayPage(1)
                                ->pdfToolbar(true)
                                ->pdfZoomLevel(100)
                                ->pdfFitType(PdfViewFit::FIT)
                                ->pdfNavPanes(true)
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(104800)
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            TextInput::make('employee_number')
                                ->required()
                            // ->unique(table: Profile::class)
                            ,
                            TextInput::make('first_name')
                                ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('middle_name')
                                ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('surname')
                                ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('suffix')
                                ->columnSpan([
                                    // 'sm' => 2,
                                ]),

                            DatePicker::make('date_of_birth')
                                // ->required()
                                ->native(false)
                                ->displayFormat('F d, Y')
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('place_of_birth')
                                // ->required()
                                ->columnSpan([
                                    'sm' => 3,
                                ]),
                            Select::make('sex')
                                // ->required()
                                ->options(Profile::SEX_SELECT),
                            TextInput::make('citizenship')
                                // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('email')
                                // ->required()
                                ->email()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('mobile_number')
                                // ->required()
                                ->prefix('+63')
                                ->maxLength(10)
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            Select::make('division_id')
                                ->label('Division')
                                ->options(Division::pluck('name', 'id'))
                                ->searchable()
                                ->columnSpan([
                                    'sm' => 3,
                                ]),
                            Select::make('status')
                                ->label('Employement Status')
                                // ->required()
                                ->options([
                                    'Permanent' => 'Permanent',
                                    'Casual' => 'Casual',
                                    'Coterminous' => 'Coterminous',
                                    'Temporary' => 'Temporary',
                                    'Job Order' => 'Job Order',
                                ])
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            TextInput::make('present_address')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            TextInput::make('permanent_address')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            TextInput::make('gsis_id_no')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 4,
                                ]),
                            TextInput::make('pagibig_id_no')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('philhealth_no')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('sss_no')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('tin_no')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),

                            TextInput::make('spouse_first_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 3,
                                ]),
                            TextInput::make('spouse_middle_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('spouse_surname')
                                // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('father_first_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 3,
                                ]),
                            TextInput::make('father_middle_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('father_surname')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('mother_first_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 3,
                                ]),
                            TextInput::make('mother_middle_name')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            TextInput::make('mother_surname')
                            // ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                ]),
                            Repeater::make('children')
                                ->relationship()
                                ->columnSpan([
                                    'sm' => 8,
                                ])
                                ->schema([
                                    TextInput::make('name')
                                    // ->required()
                                    ,
                                    DatePicker::make('date_of_birth')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                    ,
                                ])
                                ->columns(2),
                            Repeater::make('organizations')
                                ->relationship()
                                ->columnSpan([
                                    'sm' => 8,
                                ])
                                ->columns(3)
                                ->schema([
                                    TextInput::make('organization_name')
                                    // ->required()
                                    // ->columnSpan([
                                    //     'sm' => 3,
                                    // ])
                                    ,
                                    TextInput::make('organization_address')
                                    // ->required()
                                    // ->columnSpan([
                                    //     'sm' => 3,
                                    // ])
                                    ,
                                    TextInput::make('position_title')
                                    // ->required()
                                    // ->columnSpan([
                                    //     'sm' => 2,
                                    // ])
                                    ,
                                    DatePicker::make('from')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                    // ->columnSpan([
                                    //     'sm' => 1,
                                    // ])
                                    ,
                                    DatePicker::make('to')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                    // ->columnSpan([
                                    //     'sm' => 2,
                                    // ])
                                    ,
                                ]),
                            Repeater::make('skills')
                                ->relationship()
                                ->columnSpan([
                                    'sm' => 8,
                                ])
                                ->schema([
                                    TextInput::make('name')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 8,
                                        ]),

                                ]),

                        ]),

                    Step::make('Education')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        ->schema([
                            Repeater::make('educationalBackgrounds')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    DatePicker::make('from')
                                    // ->required()
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    DatePicker::make('to')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    Select::make('level')
                                    // ->required()
                                        ->options(EducationalBackground::LEVEL_SELECT)
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('school_name')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    TextInput::make('degree_course')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    TextInput::make('year_graduated')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('highest_grade')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('honors')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    AdvancedFileUpload::make('tor')
                                        ->label('Upload Transcript of Records')
                                        ->directory('tor_files')
                                        ->pdfPreviewHeight(400)
                                        ->pdfDisplayPage(1)
                                        ->pdfToolbar(true)
                                        ->pdfZoomLevel(100)
                                        ->pdfFitType(PdfViewFit::FIT)
                                        ->pdfNavPanes(true)
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    AdvancedFileUpload::make('diploma')
                                        ->label('Upload Diploma')
                                        ->directory('diploma_files')
                                        ->pdfPreviewHeight(400)
                                        ->pdfDisplayPage(1)
                                        ->pdfToolbar(true)
                                        ->pdfZoomLevel(100)
                                        ->pdfFitType(PdfViewFit::FIT)
                                        ->pdfNavPanes(true)
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                ]),

                        ]),
                    Step::make('Eligibility')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        ->schema([
                            Repeater::make('eligibilities')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    Select::make('eligibility')
                                        ->options(Eligibility::ELIGIBILITY_SELECT)
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('rating'),
                                    DatePicker::make('date_of_examination')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('place_of_examination')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('license_no')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    DatePicker::make('date_issued')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    AdvancedFileUpload::make('attachment')
                                        ->label('Upload Attachment')
                                        ->directory('eligibility_files')
                                        ->pdfPreviewHeight(400)
                                        ->pdfDisplayPage(1)
                                        ->pdfToolbar(true)
                                        ->pdfZoomLevel(100)
                                        ->pdfFitType(PdfViewFit::FIT)
                                        ->pdfNavPanes(true)
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                ])
                                ->columns(2),
                        ]),
                    Step::make('Work Experience')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        // ->description('Control who can view it')
                        ->schema([
                            Repeater::make('workExperiences')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    DatePicker::make('from')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    DatePicker::make('to')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('position_title')
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    TextInput::make('agency')
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    TextInput::make('monthly_salary')
                                        ->numeric()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('salary_grade')
                                        ->numeric()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('appointment_status')
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    Toggle::make('government')
                                        ->label('Government')
                                        ->onIcon('heroicon-m-check')
                                        ->offIcon('heroicon-m-x-mark')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->inline(false)
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    AdvancedFileUpload::make('coe')
                                        ->label('Upload Certificate of Employment')
                                        ->directory('coe_files')
                                        ->pdfPreviewHeight(400)
                                        ->pdfDisplayPage(1)
                                        ->pdfToolbar(true)
                                        ->pdfZoomLevel(100)
                                        ->pdfFitType(PdfViewFit::FIT)
                                        ->pdfNavPanes(true)
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->columnSpan([
                                            'sm' => 8,
                                        ]),
                                ]),
                        ]),
                    Step::make('Trainings')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin', 'HRIS Training Encoder', 'HRIS GIP', 'Hris Gip', 'Hris GIP']))
                        ->schema([
                            Repeater::make('trainings')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    TextInput::make('title')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    DatePicker::make('from')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    DatePicker::make('to')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('number_of_hours')
                                        ->suffix('hours')
                                    // ->required()
                                    ,
                                    TextInput::make('conducted_by')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    Select::make('ld_type')
                                        ->options(Training::LD_TYPE_SELECT)
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 3,
                                        ]),
                                    AdvancedFileUpload::make('attachment')
                                        ->label('Upload Certificate')
                                        ->directory('training_files')
                                        ->pdfPreviewHeight(400)
                                        ->pdfDisplayPage(1)
                                        ->pdfToolbar(true)
                                        ->pdfZoomLevel(100)
                                        ->pdfFitType(PdfViewFit::FIT)
                                        ->pdfNavPanes(true)
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    // Forms\Components\FileUpload::make('attachment')
                                    // ->label('Upload Certificate')
                                    // ->directory('training_files')
                                    // ->getUploadedFileNameForStorageUsing(
                                    //     fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    //         ->prepend(''),
                                    // )
                                    // ->columnSpan([
                                    //     'sm' => 4,
                                    // ]),
                                ]),
                            // ->columns(2)
                        ]),
                    Step::make('Service Records')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        ->schema([
                            Repeater::make('serviceRecords')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    Datepicker::make('from')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 1,
                                        ]),
                                    Datepicker::make('to')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 1,
                                        ]),
                                    TextInput::make('position')
                                        ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    Select::make('status')
                                        ->options([
                                            'Permanent' => 'Permanent',
                                            'Casual' => 'Casual',
                                            'Coterminous' => 'Coterminous',
                                            'Temporary' => 'Temporary',
                                            'Job Order' => 'Job Order',
                                        ])
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('sg')
                                        ->numeric()
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('increment')
                                        ->numeric()
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('salary')
                                        ->numeric()
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('allowance')
                                        ->numeric()
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('code')
                                    // ->numeric()
                                    // ->required()
                                        ->required()
                                        ->columnSpan([
                                            'sm' => 2,
                                        ]),
                                    TextInput::make('agency')
                                    // ->numeric()
                                    // ->required()
                                        ->required()
                                        ->columnSpan([
                                            'sm' => 6,
                                        ]),
                                    Textarea::make('remarks')
                                        ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    Textarea::make('other_remarks')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),

                                ]),

                            // ->columns(3)
                        ]),
                    Step::make('Awards')
                        ->disabled(! auth()->user()->hasAnyRole(['super_admin', 'HRIS Admin']))
                        ->schema([
                            Repeater::make('awards')
                                ->relationship()
                                ->columns([
                                    'sm' => 3,
                                    'xl' => 6,
                                    '2xl' => 8,
                                ])
                                ->schema([
                                    DatePicker::make('date_received')
                                        ->native(false)
                                        ->displayFormat('F d, Y')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 1,
                                        ]),
                                    TextInput::make('awards_received')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 3,
                                        ]),
                                    TextInput::make('particulars')
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 4,
                                        ]),
                                    Select::make('category')
                                        ->options(Award::CATEGORY_SELECT)
                                    // ->required()
                                        ->columnSpan([
                                            'sm' => 3,
                                        ]),
                                ]),
                            // ->columns(2)
                        ]),
                ])->skippable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Profile::orderBy('id', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['surname', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sex')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('present_address')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // now opens full page
            ],
                position: ActionsPosition::AfterColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'create' => Pages\CreateProfile::route('/create'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }
}
