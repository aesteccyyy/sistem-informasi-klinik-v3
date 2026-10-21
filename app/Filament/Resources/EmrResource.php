<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatusEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\EmrResource\Pages;
use App\Filament\Resources\EmrVisitResource;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmrResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'tabler-clipboard-heart';

    protected static ?string $navigationLabel = 'EMR';

    protected static ?string $modelLabel = 'EMR';

    protected static ?string $pluralModelLabel = 'EMR';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $user = auth()->user();
                if ($user && !$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
                    $query->whereHas('clinic_user', fn (Builder $clinicUser) =>
                        $clinicUser->whereIn('clinic_id', $user->clinics()->select('clinics.id'))
                    );
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('visit_number')
                    ->label('Nomor Kunjungan')->sortable(),
                Tables\Columns\TextColumn::make('queue_code')
                    ->label('Kode Antrian')->badge()->color('info'),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Pasien')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('patient.nik')
                    ->label('NIK'),
                Tables\Columns\TextColumn::make('patient.phone')
                    ->label('Telepon'),
                Tables\Columns\TextColumn::make('patient.birth_date')
                    ->label('Tanggal Lahir')->date(),
                Tables\Columns\TextColumn::make('clinic_user.user.name')
                    ->label('Dokter')->sortable(),
                Tables\Columns\TextColumn::make('schedule_date')
                    ->label('Tanggal Appointment')->date()->sortable(),
                Tables\Columns\TextColumn::make('symptoms')
                    ->label('Status EMR')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Terisi' : 'Belum diisi')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Appointment')->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AppointmentStatusEnum
                        ? Str::upper($state->label())
                        : Str::upper((string) $state)),
            ])
            ->filters([
                Filter::make('patient_and_visit_search')
                    ->label('Pencarian kunjungan')
                    ->form([
                        Forms\Components\DatePicker::make('visit_date')
                            ->label('Tanggal kunjungan')
                            ->placeholder('Semua tanggal')
                            ->default(today())
                            ->suffixAction(
                                FormAction::make('clear_date')
                                    ->label('Tampilkan semua tanggal')
                                    ->icon('heroicon-m-x-mark')
                                    ->action(fn (Set $set) => $set('visit_date', null)),
                            ),
                        Forms\Components\TextInput::make('patient_name')
                            ->label('Nama pasien'),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal lahir'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->default()
                    ->query(function (Builder $query, array $data): Builder {
                        $query->when(
                            filled($data['visit_date'] ?? null),
                            fn (Builder $query) => $query->whereDate('schedule_date', $data['visit_date']),
                        );

                        if (blank($data['patient_name'] ?? null) && blank($data['birth_date'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('patient', function (Builder $patientQuery) use ($data): void {
                            $patientQuery
                                ->when(filled($data['patient_name'] ?? null), fn (Builder $query) =>
                                    $query->where('name', 'like', '%'.trim($data['patient_name']).'%')
                                )
                                ->when(filled($data['birth_date'] ?? null), fn (Builder $query) =>
                                    $query->whereDate('birth_date', $data['birth_date'])
                                );
                        });
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->filtersApplyAction(fn (Tables\Actions\Action $action) => $action->label('Cari'))
            ->actions([
                Tables\Actions\Action::make('open_emr')
                    ->label('Isi / Buka EMR')
                    ->icon('tabler-notes')
                    ->url(fn (Appointment $record): string => EmrVisitResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('schedule_date', 'desc')
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEmrs::route('/')];
    }
}
