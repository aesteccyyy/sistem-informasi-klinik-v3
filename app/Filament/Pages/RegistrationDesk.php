<?php

namespace App\Filament\Pages;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicUser;
use App\Models\District;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Set;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RegistrationDesk extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Loket Pendaftaran';
    protected static ?string $title = 'Loket Pendaftaran';
    protected static ?string $navigationGroup = 'Registration Counter';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.registration-desk';

    public ?array $data = [];
    public ?Appointment $registeredAppointment = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Appointment::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'patient_type' => 'existing',
            'clinic_id' => $this->clinics()->first()?->id,
            'schedule_date' => today()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data kunjungan')
                    ->description('Pilih pasien, klinik, dokter, dan tanggal kunjungan untuk membuat nomor antrean.')
                    ->columns(2)
                    ->schema([
                        Radio::make('patient_type')
                            ->label('Jenis pendaftaran pasien')
                            ->options([
                                'existing' => 'Pasien lama',
                                'new' => 'Pasien baru',
                            ])
                            ->default('existing')
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('patient_id')
                            ->label('Pilih pasien lama')
                            ->options(fn (): array => Patient::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                            ->getSearchResultsUsing(fn (string $search): array => Patient::query()
                                ->where(function (Builder $query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('nik', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                })->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Patient::find($value)?->name)
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('patient_type') === 'existing')
                            ->visible(fn (Get $get): bool => $get('patient_type') === 'existing'),
                        Section::make('Data pasien baru')
                            ->description('Isi data dasar pasien. Data lainnya dapat dilengkapi kemudian di menu Pasien.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('new_patient_name')
                                    ->label('Nama lengkap')
                                    ->required(fn (Get $get): bool => $get('patient_type') === 'new')
                                    ->maxLength(255),
                                TextInput::make('new_patient_nik')
                                    ->label('NIK')
                                    ->length(16)
                                    ->rule('digits:16')
                                    ->unique('patients', 'nik')
                                    ->helperText('Opsional'),
                                TextInput::make('new_patient_phone')
                                    ->label('Nomor telepon')
                                    ->tel()
                                    ->unique('patients', 'phone')
                                    ->maxLength(100),
                                DatePicker::make('new_patient_birth_date')
                                    ->label('Tanggal lahir')
                                    ->required(fn (Get $get): bool => $get('patient_type') === 'new')
                                    ->native(false),
                                Select::make('new_patient_gender')
                                    ->label('Jenis kelamin')
                                    ->options([
                                        GenderEnum::MALE->value => GenderEnum::MALE->label(),
                                        GenderEnum::FEMALE->value => GenderEnum::FEMALE->label(),
                                    ])
                                    ->required(fn (Get $get): bool => $get('patient_type') === 'new')
                                    ->native(false),
                                Textarea::make('new_patient_address')
                                    ->label('Alamat lengkap')
                                    ->columnSpanFull(),
                                Select::make('new_patient_province_id')
                                    ->label('Provinsi')
                                    ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('new_patient_regency_id', null);
                                        $set('new_patient_district_id', null);
                                        $set('new_patient_village_id', null);
                                    }),
                                Select::make('new_patient_regency_id')
                                    ->label('Kabupaten/Kota')
                                    ->options(fn (Get $get): array => Regency::query()
                                        ->where('province_id', $get('new_patient_province_id'))
                                        ->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('new_patient_district_id', null);
                                        $set('new_patient_village_id', null);
                                    }),
                                Select::make('new_patient_district_id')
                                    ->label('Kecamatan')
                                    ->options(fn (Get $get): array => District::query()
                                        ->where('regency_id', $get('new_patient_regency_id'))
                                        ->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('new_patient_village_id', null)),
                                Select::make('new_patient_village_id')
                                    ->label('Desa/Kelurahan')
                                    ->options(fn (Get $get): array => Village::query()
                                        ->where('district_id', $get('new_patient_district_id'))
                                        ->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable(),
                            ])
                            ->visible(fn (Get $get): bool => $get('patient_type') === 'new')
                            ->columnSpanFull(),
                        Select::make('clinic_id')
                            ->label('Klinik')
                            ->options(fn (): array => $this->clinics()->pluck('name', 'id')->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('clinic_user_id', null)),
                        Select::make('clinic_user_id')
                            ->label('Dokter')
                            ->options(fn (Get $get): array => ClinicUser::query()
                                ->with('user')
                                ->where('clinic_id', $get('clinic_id'))
                                ->whereHas('user', fn (Builder $query) => $query->where('role', RoleEnum::DOCTOR->value))
                                ->get()
                                ->mapWithKeys(fn (ClinicUser $clinicUser): array => [$clinicUser->id => $clinicUser->user->name])
                                ->all())
                            ->searchable()
                            ->required(),
                        DatePicker::make('schedule_date')
                            ->label('Tanggal kunjungan')
                            ->default(today())
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function register(): void
    {
        abort_unless(auth()->user()?->can('create', Appointment::class), 403);

        $data = $this->form->getState();
        abort_unless($this->clinics()->contains('id', (int) $data['clinic_id']), 403);
        abort_unless(ClinicUser::query()
            ->whereKey($data['clinic_user_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->whereHas('user', fn (Builder $query) => $query->where('role', RoleEnum::DOCTOR->value))
            ->exists(), 422);

        $appointment = DB::transaction(function () use ($data): Appointment {
            $patientId = $data['patient_type'] === 'new'
                ? Patient::create([
                    'name' => $data['new_patient_name'],
                    'nik' => $data['new_patient_nik'] ?? null,
                    'phone' => $data['new_patient_phone'] ?? null,
                    'birth_date' => $data['new_patient_birth_date'],
                    'gender' => $data['new_patient_gender'],
                    'address' => $data['new_patient_address'] ?? null,
                    'province_id' => $data['new_patient_province_id'] ?? null,
                    'regency_id' => $data['new_patient_regency_id'] ?? null,
                    'district_id' => $data['new_patient_district_id'] ?? null,
                    'village_id' => $data['new_patient_village_id'] ?? null,
                ])->getKey()
                : $data['patient_id'];

            return Appointment::create([
                'patient_id' => $patientId,
            'clinic_user_id' => $data['clinic_user_id'],
            'schedule_date' => $data['schedule_date'],
            ]);
        });

        $this->registeredAppointment = $appointment->load(['patient', 'clinic_user.clinic', 'clinic_user.user']);
        Notification::make()->title('Pendaftaran berhasil')
            ->body("Nomor antrean {$appointment->queue_code} sudah dibuat.")
            ->success()->send();

        $this->form->fill([
            'patient_type' => 'existing',
            'clinic_id' => $data['clinic_id'],
            'schedule_date' => $data['schedule_date'],
        ]);
    }

    public function clinics()
    {
        $user = auth()->user();
        $query = Clinic::query()->orderBy('name');

        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
            $query->whereIn('clinics.id', $user->clinics()->select('clinics.id'));
        }

        return $query->get();
    }
}
