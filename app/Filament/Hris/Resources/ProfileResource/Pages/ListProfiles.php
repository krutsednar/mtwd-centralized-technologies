<?php

namespace App\Filament\Hris\Resources\ProfileResource\Pages;

use App\Models\User;
use App\Models\Award;
use Filament\Actions;
use App\Models\Profile;
use App\Models\Division;
use App\Models\Training;
use App\Models\Eligibility;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use App\Models\EducationalBackground;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Asmit\FilamentUpload\Enums\PdfViewFit;
use Filament\Forms\Components\Wizard\Step;
use App\Filament\Hris\Resources\ProfileResource;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // New action to update division_id in profiles
            // Actions\Action::make('updateProfileDivisions')
            //     ->label('Update Profile Divisions')
            //     ->icon('heroicon-m-arrow-path') // A suitable icon for a sync/update action
            //     ->color('primary')
            //     ->requiresConfirmation() // Always good practice for actions that modify data
            //     ->modalHeading('Confirm Profile Division Update')
            //     ->modalSubheading('Are you sure you want to update the division ID for all profiles based on their corresponding user records? This action cannot be undone.')
            //     ->modalButton('Yes, Update Divisions')
            //     ->action(function (): void {
            //         $updatedCount = 0;

            //         // Fetch all users to iterate through them
            //         // You might want to paginate this if you have a very large number of users
            //         $users = User::all();

            //         foreach ($users as $user) {
            //             // Find all profiles associated with the current user's employee_number
            //             // Assuming employee_number is the link between User and Profile
            //             $profilesToUpdate = Profile::where('employee_number', $user->employee_number)->get();

            //             foreach ($profilesToUpdate as $profile) {
            //                 // Only update if the division_id is different to avoid unnecessary database writes
            //                 if ($profile->division_id !== $user->division_id) {
            //                     $profile->division_id = $user->division_id;
            //                     $profile->save();
            //                     $updatedCount++;
            //                 }
            //             }
            //         }

            //         // Send a notification to the user about the outcome
            //         Notification::make()
            //             ->title("Successfully updated division IDs for {$updatedCount} profile(s).")
            //             ->success()
            //             ->send();
            //     }),
            Actions\CreateAction::make()
                    ->label('Create Employee Profile')
                    ->icon('heroicon-m-plus-circle')
                    ->color('info')
                    ->modalWidth('7xl')
                    ->extraAttributes(['class' => 'profile-modal'])
                    ->closeModalByClickingAway(false)
                    ->model(Profile::class)
                    ->steps([
                        Step::make('Primary Info')
                            ->afterValidation(function () {
                                // ...
                            })
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
                                    ->columnSpan([
                                        'sm' => 4,
                                    ]),
                                TextInput::make('employee_number')
                                    // ->required()
                                    ->unique(table: Profile::class)
                                    ,
                                    TextInput::make('first_name')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),
                                TextInput::make('middle_name')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),
                                TextInput::make('surname')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),
                                TextInput::make('suffix')
                                    // ->required()
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
                                    ])
                                    ,

                                    ]),


                            ]),

                        Step::make('Education')
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
                                ])

                            ]),
                        Step::make('Eligibility')

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
                                    TextInput::make('rating')
                                    ,
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
                                ->columns(2)
                            ]),
                        Step::make('Work Experience')
                            // ->description('Control who can view it')
                            ->schema([
                                Repeater::make('work_experiences')
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
                                ])
                            ]),
                            Step::make('Trainings')

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
                                    ])
                                    ,
                                    DatePicker::make('from')
                                    ->native(false)
                                    ->displayFormat('F d, Y')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    DatePicker::make('to')
                                    ->native(false)
                                    ->displayFormat('F d, Y')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('number_of_hours')
                                    // ->required()
                                    ,
                                    TextInput::make('conducted_by')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 4,
                                    ])
                                    ,
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
                                ])
                                // ->columns(2)
                            ]),
                            Step::make('Service Records')

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
                                    ])
                                    ,
                                    Datepicker::make('to')
                                    ->native(false)
                                    ->displayFormat('F d, Y')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 1,
                                    ])
                                    ,
                                    TextInput::make('position')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 4,
                                    ])
                                    ,
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
                                    ])
                                    ,
                                    TextInput::make('sg')
                                    ->numeric()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('increment')
                                    ->numeric()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('salary')
                                    ->numeric()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('allowance')
                                    ->numeric()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('code')
                                    // ->numeric()
                                    // ->required()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ])
                                    ,
                                    TextInput::make('agency')
                                    // ->numeric()
                                    // ->required()
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 6,
                                    ])
                                    ,
                                    Textarea::make('remarks')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 4,
                                    ])
                                    ,

                                    Textarea::make('other_remarks')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 4,
                                    ]),

                                    ]),


                                // ->columns(3)
                            ]),
                            Step::make('Awards')

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
                                    ])
                                    ,
                                    TextInput::make('awards_received')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 3,
                                    ])
                                    ,
                                    TextInput::make('particulars')
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 4,
                                    ])
                                    ,
                                    Select::make('category')
                                    ->options(Award::CATEGORY_SELECT)
                                    // ->required()
                                    ->columnSpan([
                                        'sm' => 3,
                                    ]),
                                ])
                                // ->columns(2)
                            ]),
                    ])
                    ->skippableSteps(),
        ];
    }
}
