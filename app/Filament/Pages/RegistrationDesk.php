<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicUser;
use App\Models\Patient;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
    protected static ?string $navigationGroup = 'Pendaftaran & Antrian';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.registration-desk';

    public ?array $data = [];
    public ?Appointment $registeredAppointment = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Appointment::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $clinics = $this->clinics();
        $requestedClinicId = (int) request()->query('clinic');
        $clinicId = $clinics->contains('id', $requestedClinicId)
            ? $requestedClinicId
            : $clinics->first()?->id;

        $this->form->fill([
            'clinic_id' => $clinicId,
            'schedule_date' => today()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data kunjungan')
                    ->description('Pilih pasien yang sudah terdaftar di menu Patients, lalu tentukan klinik, dokter, dan tanggal kunjungan.')
                    ->columns(2)
                    ->schema([
                        Select::make('patient_id')
                            ->label('Pilih pasien terdaftar')
                            ->options(fn (): array => Patient::query()->orderBy('name')->limit(50)->get()->mapWithKeys(fn (Patient $patient): array => [$patient->id => $this->patientLabel($patient)])->all())
                            ->getSearchResultsUsing(fn (string $search): array => Patient::query()
                                ->where(function (Builder $query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('nik', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%")
                                        ->orWhere('medical_record_number', 'like', "%{$search}%");
                                })->orderBy('name')->limit(50)->get()->mapWithKeys(fn (Patient $patient): array => [$patient->id => $this->patientLabel($patient)])->all())
                            ->getOptionLabelUsing(fn ($value): ?string => ($patient = Patient::find($value)) ? $this->patientLabel($patient) : null)
                            ->searchable()
                            ->required()
                            ->helperText('Jika pasien belum tersedia, daftarkan dahulu melalui menu Patients.'),
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
        abort_unless(isset($data['patient_id']) && Patient::query()->whereKey($data['patient_id'])->exists(), 422);
        abort_unless(ClinicUser::query()
            ->whereKey($data['clinic_user_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->whereHas('user', fn (Builder $query) => $query->where('role', RoleEnum::DOCTOR->value))
            ->exists(), 422);

        $appointment = DB::transaction(function () use ($data): Appointment {
            return Appointment::create([
                'patient_id' => $data['patient_id'],
                'clinic_user_id' => $data['clinic_user_id'],
                'schedule_date' => $data['schedule_date'],
            ]);
        });

        $this->registeredAppointment = $appointment->load(['patient', 'clinic_user.clinic', 'clinic_user.user']);
        Notification::make()->title('Pendaftaran berhasil')
            ->body("Nomor antrean {$appointment->queue_code} sudah dibuat.")
            ->success()->send();

        $this->form->fill([
            'patient_id' => null,
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

    private function patientLabel(Patient $patient): string
    {
        return $patient->name . ' · RM ' . ($patient->medical_record_number ?: 'belum ada');
    }
}
