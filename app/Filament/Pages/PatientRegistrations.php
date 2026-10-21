<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Enums\AppointmentStatusEnum;
use App\Enums\QueueStatusEnum;
use App\Filament\Resources\EmrVisitResource;
use App\Models\Appointment;
use App\Models\CareEncounter;
use App\Models\CareBed;
use App\Models\Clinic;
use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientRegistrations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Registrasi';
    protected static ?string $title = 'Registrasi Pasien';
    protected static ?string $navigationGroup = 'Pendaftaran & Antrian';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.patient-registrations';

    protected ?string $heading = '';
    protected ?string $maxContentWidth = 'full';

    public ?int $clinicId = null;
    public string $registrationDate = '';
    public string $serviceType = 'all';
    public string $search = '';
    public bool $showVoidModal = false;
    public string $voidType = '';
    public ?int $voidId = null;
    public string $voidReason = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->can('viewAny', Appointment::class)
            || $user->can('create', Appointment::class)
            || static::clinicsFor($user)->contains(fn (Clinic $clinic): bool => $clinic->has_inpatient || $clinic->has_emergency)
        );
    }

    public function mount(): void
    {
        $clinics = $this->clinics();
        $requestedClinicId = (int) request()->query('clinic');
        $this->clinicId = $clinics->contains('id', $requestedClinicId)
            ? $requestedClinicId
            : $clinics->first()?->id;
        $this->registrationDate = today()->toDateString();
    }

    public function render(): View
    {
        return view(static::$view, [
            'clinics' => $this->clinics(),
            'registrations' => $this->registrations(),
            'canVoidRegistrations' => $this->canManageRegistrationVoids(),
        ]);
    }

    public function newRegistrationUrl(): ?string
    {
        $clinic = $this->clinics()->firstWhere('id', (int) $this->clinicId);
        if (!$clinic || !PatientRegistrationForm::canAccess()) return null;

        return PatientRegistrationForm::getUrl(['clinic' => $clinic->id]);
    }

    public function updatedClinicId(): void
    {
        abort_unless($this->clinicId === null || $this->clinics()->contains('id', (int) $this->clinicId), 403);
    }

    public function updatedServiceType(): void
    {
        abort_unless(in_array($this->serviceType, ['all', 'outpatient', 'inpatient', 'emergency'], true), 422);
    }

    public function openVoidModal(string $type, int $id): void
    {
        abort_unless($this->canManageRegistrationVoids(), 403);
        abort_unless(in_array($type, ['outpatient', 'inpatient', 'emergency'], true), 422);

        $registration = $this->findRegistrationForVoid($type, $id);
        abort_unless($this->registrationCanBeVoided($registration, $type), 422, 'Registrasi ini tidak dapat dibatalkan.');

        $this->voidType = $type;
        $this->voidId = $id;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function cancelVoid(): void
    {
        $this->showVoidModal = false;
        $this->voidType = '';
        $this->voidId = null;
        $this->voidReason = '';
        $this->resetValidation();
    }

    public function voidRegistration(): void
    {
        abort_unless($this->canManageRegistrationVoids(), 403);
        $data = $this->validate([
            'voidType' => ['required', 'in:outpatient,inpatient,emergency'],
            'voidId' => ['required', 'integer', 'min:1'],
            'voidReason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'voidReason.required' => 'Alasan pembatalan wajib diisi.',
            'voidReason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $reason = trim($data['voidReason']);
        if ($reason === '') {
            throw ValidationException::withMessages(['voidReason' => 'Alasan pembatalan wajib diisi.']);
        }

        if ($data['voidType'] === 'outpatient') {
            DB::transaction(function () use ($data, $reason): void {
                $appointment = Appointment::query()
                    ->whereIn('clinic_id', $this->clinics()->pluck('id'))
                    ->lockForUpdate()
                    ->findOrFail($data['voidId']);
                if (!$this->registrationCanBeVoided($appointment, 'outpatient')) {
                    throw ValidationException::withMessages(['voidReason' => 'Kunjungan ini sudah diproses atau memiliki transaksi sehingga tidak dapat dibatalkan.']);
                }

                $appointment->forceFill([
                    'queue_status' => QueueStatusEnum::SKIPPED->value,
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'void_reason' => $reason,
                ])->saveQuietly();
            });
        } else {
            DB::transaction(function () use ($data, $reason): void {
                $careType = $data['voidType'] === 'inpatient' ? 'inpatient' : 'emergency';
                $encounter = CareEncounter::query()
                    ->whereIn('clinic_id', $this->clinics()->pluck('id'))
                    ->where('care_type', $careType)
                    ->lockForUpdate()
                    ->findOrFail($data['voidId']);
                if (!$this->registrationCanBeVoided($encounter, $data['voidType'])) {
                    throw ValidationException::withMessages(['voidReason' => 'Kunjungan ini sudah memiliki catatan pelayanan dan harus diselesaikan melalui resume medis.']);
                }

                if ($encounter->care_bed_id) {
                    $bed = CareBed::query()->lockForUpdate()->find($encounter->care_bed_id);
                    if ($bed && $bed->status === 'occupied') $bed->update(['status' => 'available']);
                }

                $encounter->forceFill([
                    'status' => 'voided',
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'void_reason' => $reason,
                ])->save();
            });
        }

        $this->cancelVoid();
        Notification::make()->title('Registrasi dibatalkan')->body('Alasan pembatalan tersimpan pada riwayat registrasi.')->success()->send();
    }

    public function registrations(): Collection
    {
        $clinicIds = $this->clinics()->pluck('id');
        if ($this->clinicId) {
            abort_unless($clinicIds->contains((int) $this->clinicId), 403);
            $clinicIds = collect([(int) $this->clinicId]);
        }

        $registrations = collect();
        $term = trim($this->search);
        $user = auth()->user();
        $canSeeOutpatient = $user?->can('viewAny', Appointment::class) || $user?->can('create', Appointment::class);

        if ($canSeeOutpatient && in_array($this->serviceType, ['all', 'outpatient'], true)) {
            Appointment::query()
                ->with(['patient', 'clinic_user.user', 'clinic_user.clinic'])
                ->whereIn('clinic_id', $clinicIds)
                ->where(fn ($query) => $query
                    ->whereDate('schedule_date', $this->registrationDate)
                    ->orWhereDate('voided_at', $this->registrationDate))
                ->when($term !== '', fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where(function ($match) use ($term): void {
                    $match->where('name', 'like', "%{$term}%")
                        ->orWhere('nik', 'like', "%{$term}%")
                        ->orWhere('medical_record_number', 'like', "%{$term}%");
                })))
                ->orderByDesc('schedule_date')
                ->limit(200)
                ->get()
                ->each(function (Appointment $appointment) use ($registrations): void {
                    $registrations->push([
                        'medical_record_number' => $appointment->patient?->medical_record_number ?? '—',
                        'patient' => $appointment->patient?->name ?? '—',
                        'nik' => $appointment->patient?->nik ?? '—',
                        'service' => 'Rawat Jalan',
                        'service_key' => 'outpatient',
                        'date' => $appointment->schedule_date,
                        'clinic' => $appointment->clinic_user?->clinic?->name ?? '—',
                        'doctor' => $appointment->clinic_user?->user?->name ?? '—',
                        'location' => 'Poliklinik',
                        'status' => $appointment->voided_at ? 'Batal berobat' : ($appointment->status?->label() ?? 'Terdaftar'),
                        'status_color' => $appointment->voided_at ? 'danger' : ($appointment->status?->color() ?? 'gray'),
                        'url' => EmrVisitResource::getUrl('edit', ['record' => $appointment]),
                        'can_open' => !$appointment->voided_at && EmrVisitResource::canEdit($appointment),
                        'can_void' => $this->canManageRegistrationVoids() && !$appointment->voided_at && $appointment->status !== AppointmentStatusEnum::CONFIRMED,
                        'void_type' => 'outpatient',
                        'id' => $appointment->id,
                        'void_reason' => $appointment->void_reason,
                    ]);
                });
        }

        if (in_array($this->serviceType, ['all', 'inpatient', 'emergency'], true)) {
            $careTypes = match ($this->serviceType) {
                'inpatient' => ['inpatient'],
                'emergency' => ['emergency'],
                default => ['inpatient', 'emergency'],
            };
            CareEncounter::query()
                ->with(['patient', 'clinic', 'room', 'bed', 'doctor.user'])
                ->withCount([
                    'medications as administered_medications_count' => fn ($query) => $query->where('administered_quantity', '>', 0),
                    'procedures',
                ])
                ->whereIn('clinic_id', $clinicIds)
                ->whereIn('care_type', $careTypes)
                ->where(function ($query): void {
                    $query->where('status', 'active')
                        ->orWhereDate('arrived_at', $this->registrationDate)
                        ->orWhereDate('voided_at', $this->registrationDate);
                })
                ->when($term !== '', fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where(function ($match) use ($term): void {
                    $match->where('name', 'like', "%{$term}%")
                        ->orWhere('nik', 'like', "%{$term}%")
                        ->orWhere('medical_record_number', 'like', "%{$term}%");
                })))
                ->orderByDesc('arrived_at')
                ->limit(200)
                ->get()
                ->each(function (CareEncounter $encounter) use ($registrations): void {
                    $isEmergency = $encounter->care_type === 'emergency';
                    $registrations->push([
                        'medical_record_number' => $encounter->patient?->medical_record_number ?? '—',
                        'patient' => $encounter->patient?->name ?? '—',
                        'nik' => $encounter->patient?->nik ?? '—',
                        'service' => $isEmergency ? 'IGD' : 'Rawat Inap',
                        'service_key' => $isEmergency ? 'emergency' : 'inpatient',
                        'date' => $encounter->arrived_at,
                        'clinic' => $encounter->clinic?->name ?? '—',
                        'doctor' => $encounter->doctor?->user?->name ?? '—',
                        'location' => $encounter->room?->name
                            ? $encounter->room->name . ' / ' . ($encounter->bed?->code ?? '—')
                            : 'Belum ditempatkan',
                        'status' => $encounter->status === 'voided'
                            ? 'Batal berobat'
                            : ($encounter->status === 'active' ? ($isEmergency ? 'Dalam penanganan' : 'Dalam perawatan') : 'Selesai'),
                        'status_color' => $encounter->status === 'voided' ? 'danger' : ($encounter->status === 'active' ? 'success' : 'gray'),
                        'url' => ($isEmergency ? EmergencyCare::getUrl() : InpatientCare::getUrl()) . '?clinic=' . $encounter->clinic_id . '&encounter=' . $encounter->id,
                        'can_open' => $encounter->status !== 'voided',
                        'can_void' => $this->canManageRegistrationVoids()
                            && $encounter->status === 'active'
                            && (int) $encounter->administered_medications_count === 0
                            && (int) $encounter->procedures_count === 0,
                        'void_type' => $encounter->care_type,
                        'id' => $encounter->id,
                        'void_reason' => $encounter->void_reason,
                    ]);
                });
        }

        return $registrations->sortByDesc(fn (array $registration) => $registration['date']?->getTimestamp() ?? 0)->values();
    }

    public function clinics(): Collection
    {
        return static::clinicsFor(auth()->user());
    }

    private function canManageRegistrationVoids(): bool
    {
        return auth()->user()?->hasRole([
            RoleEnum::OPERATOR->value,
            RoleEnum::DOCTOR->value,
            RoleEnum::NURSE->value,
            RoleEnum::ADMIN->value,
            RoleEnum::SUPER_ADMIN->value,
        ]) ?? false;
    }

    private function findRegistrationForVoid(string $type, int $id): Appointment|CareEncounter
    {
        $clinicIds = $this->clinics()->pluck('id');

        if ($type === 'outpatient') {
            return Appointment::query()->whereIn('clinic_id', $clinicIds)->findOrFail($id);
        }

        return CareEncounter::query()
            ->whereIn('clinic_id', $clinicIds)
            ->where('care_type', $type === 'inpatient' ? 'inpatient' : 'emergency')
            ->findOrFail($id);
    }

    private function registrationCanBeVoided(Appointment|CareEncounter $registration, string $type): bool
    {
        if ($registration instanceof Appointment) {
            return !$registration->voided_at
                && $registration->status !== AppointmentStatusEnum::CONFIRMED
                && !Transaction::query()->where('appointment_id', $registration->id)->exists();
        }

        return $registration->status === 'active'
            && !$registration->medications()->where('administered_quantity', '>', 0)->exists()
            && !$registration->procedures()->exists();
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
