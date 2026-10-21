<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatusEnum;
use App\Enums\QueueStatusEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected $listeners = ['refreshAppointment' => '$refresh'];

    protected static ?string $navigationIcon = 'tabler-calendar-clock';

    // protected static ?string $modelLabel = 'employee';
    // protected static ?string $pluralModelLabel = 'employees';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Poliklinik';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Appointment Data'))
                    // ->label('Clinic\'s Basic Info')
                    ->description(__('Contains patient\'s data and their diagnostic info'))
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('visit_number')
                            ->label('Nomor Kunjungan')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Dibuat otomatis setelah kunjungan disimpan'),
                        Forms\Components\TextInput::make('queue_code')
                            ->label('Kode Antrian')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Dibuat otomatis setelah appointment disimpan'),
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'name')
                            // ->hintAction(
                            //     Action::make('patient')
                            //         ->label('Patient\'s Detail')
                            //         ->icon('tabler-eye')
                            //         ->form([
                            //             Placeholder::make('name'),
                            //         ])->action(function (Set $set) {
                            //             // do something
                            //         })
                            // )
                            ->hint(
                                function ($get) {
                                    return new HtmlString('<a href="/admin/patients/' . $get('patient_id') . '" target="_blank">Patient\'s Detail</a>');
                                }
                            )
                            // ->createOptionForm([
                            //     Section::make('New Patient')
                            //         //->label('Employee\'s Basic Info')
                            //         ->schema([
                            //             Forms\Components\TextInput::make('name')
                            //                 ->required(),
                            //             Forms\Components\TextInput::make('phone')
                            //                 ->required()
                            //                 ->tel(),
                            //         ])
                            // ])
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('clinic_user_id')
                            ->label(__('Clinic : Doctor'))
                            ->relationship(
                                'clinic_doctor',
                                'id',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->whereHas('clinic')
                                    ->whereHas('user'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => sprintf(
                                '%s : %s',
                                $record->clinic?->name ?? 'Clinic unavailable',
                                $record->user?->name ?? 'Doctor unavailable',
                            ))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('schedule_date')
                            ->required(),
                    ]),
            ])->columns(3);;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
                    //$query->select('*, clinic_user.clinic_id, clinic_user.id AS c_user_id');
                    $query->select('appointments.*');
                    $query->leftJoin('clinic_user', 'clinic_user.id', '=', 'clinic_user_id');
                    $query->where('clinic_user.clinic_id', $user->clinics()->first()->id);
                    clock()->info($query->toRawSql());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Appointment')
                    ->numeric()
                    // ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clinic_user.clinic.name')
                    ->label('Clinic')
                    // ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient.name')
                    // ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_number')
                    ->label('Nomor Kunjungan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('queue_code')
                    ->label('Kode Antrian')
                    ->badge()
                    ->color('info')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('queue_number', $direction)),
                Tables\Columns\TextColumn::make('queue_status')
                    ->label('Status Antrian')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => QueueStatusEnum::tryFrom($state ?? '')?->label() ?? 'Menunggu')
                    ->color(fn (?string $state): string => match ($state) {
                        QueueStatusEnum::CALLED->value => 'primary',
                        QueueStatusEnum::SERVED->value => 'success',
                        QueueStatusEnum::SKIPPED->value => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('clinic_user.user.name')
                    ->label('Doctor')
                    // ->numeric()
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('clinic_user_id')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('schedule_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('height')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('blood_pressure')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Str::upper($state->value))
                    ->color(fn (object $state): string => match ($state) {
                        AppointmentStatusEnum::SCHEDULED => 'info',
                        AppointmentStatusEnum::DIAGNOSED => 'primary',
                        AppointmentStatusEnum::PREPARED => 'warning',
                        AppointmentStatusEnum::CONFIRMED => 'success',
                    }),
                Tables\Columns\TextColumn::make('patient.satusehat_sync_status')
                    ->label('Status SATUSEHAT Pasien')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'synced' => 'success',
                        'linked', 'syncing' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'synced' => 'Terkirim',
                        'linked' => 'Terhubung',
                        'syncing' => 'Sedang dikirim',
                        'failed' => 'Gagal',
                        default => 'Belum dikirim',
                    })
                    ->tooltip(fn ($record): ?string => $record->patient?->satusehat_sync_error),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        AppointmentStatusEnum::SCHEDULED->value => Str::upper(AppointmentStatusEnum::SCHEDULED->label()),
                        AppointmentStatusEnum::DIAGNOSED->value => Str::upper(AppointmentStatusEnum::DIAGNOSED->label()),
                        AppointmentStatusEnum::PREPARED->value => Str::upper(AppointmentStatusEnum::PREPARED->label()),
                        AppointmentStatusEnum::CONFIRMED->value => Str::upper(AppointmentStatusEnum::CONFIRMED->label()),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
