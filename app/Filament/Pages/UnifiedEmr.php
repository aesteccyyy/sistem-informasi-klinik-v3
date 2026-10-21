<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Filament\Resources\EmrVisitResource;
use App\Models\Appointment;
use App\Models\Clinic;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class UnifiedEmr extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'EMR';
    protected static ?string $title = 'EMR';
    protected static ?string $navigationGroup = 'Poliklinik';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.unified-emr';

    protected ?string $heading = '';
    protected ?string $maxContentWidth = 'full';

    public ?int $clinicId = null;
    public string $visitType = 'all';
    public string $search = '';
    public ?string $fromDate = null;
    public ?string $toDate = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('viewAny', Appointment::class);
    }

    public function mount(): void
    {
        $this->clinicId = $this->clinics()->first()?->id;
    }

    public function render(): View
    {
        $clinics = $this->clinics();
        return view(static::$view, [
            'clinics' => $clinics,
            'visits' => $this->visits(),
            'canSeeOutpatient' => auth()->user()?->can('viewAny', Appointment::class) ?? false,
        ]);
    }

    public function updatedClinicId(): void
    {
        abort_unless($this->clinicId === null || $this->clinics()->contains('id', (int) $this->clinicId), 403);
    }

    public function updatedVisitType(): void
    {
        abort_unless(in_array($this->visitType, ['all', 'outpatient'], true), 422);
    }

    public function clinics(): Collection
    {
        return static::clinicsFor(auth()->user());
    }

    public function visits(): Collection
    {
        $clinicIds = $this->clinics()->pluck('id');
        if ($this->clinicId) {
            abort_unless($clinicIds->contains($this->clinicId), 403);
            $clinicIds = collect([(int) $this->clinicId]);
        }

        $visits = collect();
        $canSeeOutpatient = auth()->user()?->can('viewAny', Appointment::class) ?? false;

        if ($canSeeOutpatient && in_array($this->visitType, ['all', 'outpatient'], true)) {
            Appointment::query()
                ->with(['patient', 'clinic_user.user', 'clinic_user.clinic'])
                ->whereIn('clinic_id', $clinicIds)
                ->whereNull('voided_at')
                ->when($this->fromDate, fn ($query) => $query->whereDate('schedule_date', '>=', $this->fromDate))
                ->when($this->toDate, fn ($query) => $query->whereDate('schedule_date', '<=', $this->toDate))
                ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($query) {
                    $term = '%' . trim($this->search) . '%';
                    $query->whereHas('patient', fn ($patient) => $patient
                        ->where('name', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('medical_record_number', 'like', $term));
                }))
                ->latest('schedule_date')
                ->limit(150)
                ->get()
                ->each(function (Appointment $appointment) use ($visits): void {
                    $visits->push([
                        'kind' => 'outpatient',
                        'kind_label' => 'Rawat Jalan · Outpatient',
                        'number' => $appointment->patient?->medical_record_number ?? '—',
                        'patient' => $appointment->patient?->name ?? '—',
                        'nik' => $appointment->patient?->nik ?? '—',
                        'clinic' => $appointment->clinic_user?->clinic?->name ?? '—',
                        'doctor' => $appointment->clinic_user?->user?->name ?? '—',
                        'date' => $appointment->schedule_date,
                        'status' => $appointment->status?->label() ?? '—',
                        'status_color' => $appointment->status?->color() ?? 'gray',
                        'location' => 'Poliklinik',
                        'url' => EmrVisitResource::getUrl('edit', ['record' => $appointment]),
                        'can_open' => EmrVisitResource::canEdit($appointment),
                    ]);
                });
        }

        return $visits->sortByDesc(fn (array $visit) => $visit['date']?->getTimestamp() ?? 0)->values();
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
