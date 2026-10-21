<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\CareBed;
use App\Models\CareEncounter;
use App\Models\CareRoom;
use App\Models\Clinic;
use App\Models\ClinicUser;
use App\Models\Patient;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientRegistrationForm extends Page
{
    protected static ?string $navigationLabel = 'Registrasi Baru';
    protected static ?string $title = 'Registrasi Baru';
    protected static ?string $navigationGroup = 'Pendaftaran & Antrian';
    protected static string $view = 'filament.pages.patient-registration-form';
    protected ?string $heading = '';
    protected ?string $maxContentWidth = 'full';

    public ?int $clinicId = null;
    public string $serviceType = 'outpatient';
    public string $patientSearch = '';
    public ?int $patientId = null;
    public array $registration = [];
    public ?Appointment $registeredAppointment = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('create', Appointment::class)
            || ($user->hasRole([RoleEnum::DOCTOR->value, RoleEnum::NURSE->value, RoleEnum::OPERATOR->value, RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value])
                && static::clinicsFor($user)->contains(fn (Clinic $clinic): bool => $clinic->has_inpatient || $clinic->has_emergency));
    }

    public function mount(): void
    {
        $clinics = $this->clinics();
        $requestedClinicId = (int) request()->query('clinic');
        $this->clinicId = $clinics->contains('id', $requestedClinicId) ? $requestedClinicId : $clinics->first()?->id;
        $requestedType = (string) request()->query('service', 'outpatient');
        $availableServices = $this->availableServices();
        $this->serviceType = in_array($requestedType, $availableServices, true)
            ? $requestedType
            : ($availableServices[0] ?? 'outpatient');
        $this->registration = [
            'schedule_date' => today()->toDateString(),
            'arrived_at' => now()->format('Y-m-d\TH:i'),
            'admission_source' => 'Poliklinik',
            'admission_method' => 'Jalan sendiri',
            'guarantor' => 'Umum',
            'triage_level' => '',
        ];
    }

    public function render(): View
    {
        return view(static::$view, [
            'clinics' => $this->clinics(),
            'patients' => $this->patientResults(),
            'selectedPatient' => $this->patientId ? Patient::query()->find($this->patientId) : null,
            'doctors' => $this->doctors(),
            'rooms' => $this->rooms(),
            'beds' => $this->beds(),
            'availableServices' => $this->availableServices(),
        ]);
    }

    public function updatedClinicId(): void
    {
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);
        $this->registration['clinic_user_id'] = null;
        $this->registration['care_room_id'] = null;
        $this->registration['care_bed_id'] = null;
        if (!$this->serviceAvailable($this->serviceType)) $this->serviceType = $this->availableServices()[0] ?? 'outpatient';
    }

    public function updatedServiceType(): void
    {
        abort_unless(in_array($this->serviceType, ['outpatient', 'inpatient', 'emergency'], true), 422);
        abort_unless($this->serviceAvailable($this->serviceType), 403);
        $this->registration['clinic_user_id'] = null;
        $this->registration['care_room_id'] = null;
        $this->registration['care_bed_id'] = null;
        $this->registration['triage_level'] = '';
        $this->registration['admission_source'] = $this->serviceType === 'emergency' ? 'Datang langsung' : 'Poliklinik';
    }

    public function updatedRegistrationCareRoomId(): void
    {
        $this->registration['care_bed_id'] = null;
    }

    public function choosePatient(int $patientId): void
    {
        abort_unless(Patient::query()->whereKey($patientId)->exists(), 404);
        $this->patientId = $patientId;
        $this->patientSearch = '';
        $this->resetValidation('patientSearch');
    }

    public function clearPatient(): void
    {
        $this->patientId = null;
    }

    public function register(): void
    {
        abort_unless($this->serviceAvailable($this->serviceType), 403);
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);

        $data = $this->validate([
            'patientId' => ['required', 'integer', 'exists:patients,id'],
            'clinicId' => ['required', 'integer'],
            'serviceType' => ['required', 'in:outpatient,inpatient,emergency'],
            'registration.clinic_user_id' => [$this->serviceType === 'outpatient' ? 'required' : 'nullable', 'nullable', 'integer'],
            'registration.schedule_date' => [$this->serviceType === 'outpatient' ? 'required' : 'nullable', 'nullable', 'date'],
            'registration.arrived_at' => [$this->serviceType !== 'outpatient' ? 'required' : 'nullable', 'nullable', 'date'],
            'registration.admission_source' => ['nullable', 'string', 'max:40'],
            'registration.admission_method' => ['nullable', 'string', 'max:40'],
            'registration.guarantor' => ['nullable', 'string', 'max:120'],
            'registration.initial_diagnosis' => [$this->serviceType === 'inpatient' && $this->isDoctor() ? 'required' : 'nullable', 'nullable', 'string', 'max:255'],
            'registration.admission_reason' => [$this->serviceType !== 'outpatient' ? 'required' : 'nullable', 'nullable', 'string', 'max:5000'],
            'registration.triage_level' => [$this->serviceType === 'emergency' ? 'required' : 'nullable', 'nullable', 'string', 'max:30'],
            'registration.care_room_id' => [$this->serviceType === 'inpatient' ? 'required' : 'nullable', 'nullable', 'integer'],
            'registration.care_bed_id' => [$this->serviceType === 'inpatient' ? 'required' : 'nullable', 'nullable', 'integer'],
        ]);

        abort_unless($this->clinics()->contains('id', (int) $data['clinicId']), 403);
        $patient = Patient::query()->findOrFail($data['patientId']);

        if ($this->serviceType === 'outpatient') {
            $doctorId = (int) $data['registration']['clinic_user_id'];
            abort_unless($this->doctorQuery()->whereKey($doctorId)->exists(), 403);
            $appointment = Appointment::query()->create([
                'patient_id' => $patient->id,
                'clinic_user_id' => $doctorId,
                'schedule_date' => $data['registration']['schedule_date'],
            ]);
            $this->registeredAppointment = $appointment->load(['patient', 'clinic_user.clinic', 'clinic_user.user']);
            $this->patientId = null;
            $this->registration['clinic_user_id'] = null;
            Notification::make()->title('Registrasi rawat jalan berhasil')->body("Nomor antrean {$appointment->queue_code} sudah dibuat.")->success()->send();
            return;
        }

        abort_unless($this->canManageCareRegistration(), 403);
        $doctorId = $data['registration']['clinic_user_id'] ?? null;
        if ($doctorId) abort_unless($this->doctorQuery()->whereKey($doctorId)->exists(), 403);

        $encounter = DB::transaction(function () use ($data, $patient, $doctorId): CareEncounter {
            $clinic = Clinic::query()->whereKey($this->clinicId)->lockForUpdate()->firstOrFail();
            $careType = $this->serviceType;
            if (CareEncounter::query()->where('clinic_id', $clinic->id)->where('patient_id', $patient->id)->where('care_type', $careType)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['patientId' => 'Pasien sudah memiliki kunjungan aktif pada layanan ini.']);
            }

            $room = null;
            $bed = null;
            if ($careType === 'inpatient') {
                $room = CareRoom::query()->whereKey($data['registration']['care_room_id'])->where('clinic_id', $clinic->id)->where('is_active', true)->firstOrFail();
                $bed = CareBed::query()->whereKey($data['registration']['care_bed_id'])->where('care_room_id', $room->id)->lockForUpdate()->firstOrFail();
                if ($bed->status !== 'available') throw ValidationException::withMessages(['registration.care_bed_id' => 'Bed ini sudah tidak tersedia. Pilih bed lain.']);
                $bed->update(['status' => 'occupied']);
            }

            $sequence = CareEncounter::query()->where('clinic_id', $clinic->id)->whereDate('created_at', today())->count() + 1;
            $number = ($careType === 'inpatient' ? 'RI' : 'IGD') . '-' . now()->format('Ymd') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            return CareEncounter::query()->create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'clinic_user_id' => $doctorId,
                'care_room_id' => $room?->id,
                'care_bed_id' => $bed?->id,
                'created_by' => auth()->id(),
                'encounter_number' => $number,
                'care_type' => $careType,
                'status' => 'active',
                'arrived_at' => $data['registration']['arrived_at'],
                'admission_source' => $data['registration']['admission_source'] ?? null,
                'admission_method' => $data['registration']['admission_method'] ?? null,
                'guarantor' => $data['registration']['guarantor'] ?? null,
                'initial_diagnosis' => $data['registration']['initial_diagnosis'] ?? null,
                'admission_reason' => $data['registration']['admission_reason'],
                'triage_level' => $data['registration']['triage_level'] ?? null,
            ]);
        });

        $page = $this->serviceType === 'inpatient' ? InpatientCare::class : EmergencyCare::class;
        $this->redirect($page::getUrl(['clinic' => $this->clinicId, 'encounter' => $encounter->id]));
    }

    public function patientResults(): Collection
    {
        $term = trim($this->patientSearch);
        if (mb_strlen($term) < 2) return collect();

        return Patient::query()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('nik', 'like', "%{$term}%")
                ->orWhere('medical_record_number', 'like', "%{$term}%"))
            ->orderBy('name')->limit(12)->get();
    }

    public function clinics(): Collection
    {
        return static::clinicsFor(auth()->user());
    }

    public function availableServices(): array
    {
        return collect(['outpatient', 'inpatient', 'emergency'])
            ->filter(fn (string $type): bool => $this->serviceAvailable($type))
            ->values()->all();
    }

    public function doctors(): Collection
    {
        return $this->clinicId ? $this->doctorQuery()->with('user')->get()->filter(fn (ClinicUser $doctor) => $doctor->user !== null) : collect();
    }

    public function rooms(): Collection
    {
        return $this->serviceType === 'inpatient' && $this->clinicId
            ? CareRoom::query()->where('clinic_id', $this->clinicId)->where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    public function beds(): Collection
    {
        return $this->serviceType === 'inpatient' && $this->clinicId && ($roomId = $this->registration['care_room_id'] ?? null)
            ? CareBed::query()->where('status', 'available')->where('care_room_id', $roomId)->whereHas('room', fn ($query) => $query->where('clinic_id', $this->clinicId)->where('is_active', true))->orderBy('code')->get()
            : collect();
    }

    private function serviceAvailable(string $type): bool
    {
        $clinic = $this->clinics()->firstWhere('id', (int) $this->clinicId);
        if (!$clinic) return false;

        return match ($type) {
            'outpatient' => auth()->user()?->can('create', Appointment::class) ?? false,
            'inpatient' => $clinic->has_inpatient && $this->canManageCareRegistration(),
            'emergency' => $clinic->has_emergency && $this->canManageCareRegistration(),
            default => false,
        };
    }

    private function doctorQuery(): Builder
    {
        return ClinicUser::query()->where('clinic_id', $this->clinicId)
            ->whereHas('user', fn (Builder $query) => $query->where('role', RoleEnum::DOCTOR->value));
    }

    private function canManageCareRegistration(): bool
    {
        return auth()->user()?->hasRole([
            RoleEnum::DOCTOR->value,
            RoleEnum::NURSE->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::ADMIN->value,
            RoleEnum::SUPER_ADMIN->value,
        ]) ?? false;
    }

    private function isDoctor(): bool
    {
        return auth()->user()?->hasRole(RoleEnum::DOCTOR->value)
            || (auth()->user()?->role?->value ?? auth()->user()?->getRawOriginal('role')) === RoleEnum::DOCTOR->value;
    }

    private static function clinicsFor($user): Collection
    {
        if (!$user) return collect();
        $query = Clinic::query()->orderBy('name');
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
            $query->whereIn('clinics.id', $user->clinics()->select('clinics.id'));
        }
        return $query->get();
    }
}
