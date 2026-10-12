<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Clinic;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;

class QueueDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';

    protected static ?string $navigationLabel = 'Dashboard Antrian';

    protected static ?string $title = 'Dashboard Antrian';

    protected static ?string $navigationGroup = 'Registration Counter';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.queue-dashboard';

    public ?int $clinicId = null;

    public string $queueDate;

    public ?string $lastCallKey = null;

    public bool $hasRenderedQueue = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Appointment::class) ?? false;
    }

    public function mount(): void
    {
        $this->queueDate = today()->toDateString();
        $this->clinicId = $this->clinics()->first()?->id;
    }

    public function render(): View
    {
        $current = $this->currentAppointment();
        $callKey = $current
            ? $current->id . '|' . $current->queue_called_at?->timestamp
            : null;

        if (
            $this->hasRenderedQueue
            && $callKey
            && $callKey !== $this->lastCallKey
            && $this->queueDate === today()->toDateString()
        ) {
            $this->dispatch('queue-called', code: $current->queue_code);
        }

        $this->lastCallKey = $callKey;
        $this->hasRenderedQueue = true;

        return view(static::$view);
    }

    public function clinics(): Collection
    {
        $user = auth()->user();
        $query = Clinic::query()->orderBy('name');

        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
            $query->whereIn('clinics.id', $user->clinics()->select('clinics.id'));
        }

        return $query->get();
    }

    public function currentAppointment(): ?Appointment
    {
        if (!$this->validFilter()) {
            return null;
        }

        return $this->queueQuery()
            ->where('queue_status', QueueStatusEnum::CALLED->value)
            ->orderByDesc('queue_called_at')
            ->first();
    }

    public function waitingAppointments(): Collection
    {
        if (!$this->validFilter()) {
            return collect();
        }

        return $this->queueQuery()
            ->where('queue_status', QueueStatusEnum::WAITING->value)
            ->orderBy('queue_number')
            ->limit(12)
            ->get();
    }

    public function updatedClinicId(): void
    {
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);
    }

    public function updatedQueueDate(): void
    {
        $this->validate(['queueDate' => ['required', 'date_format:Y-m-d']]);
    }

    private function validFilter(): bool
    {
        if (!$this->clinicId || !$this->queueDate) {
            return false;
        }

        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);
        return Carbon::hasFormat($this->queueDate, 'Y-m-d');
    }

    private function queueQuery(): Builder
    {
        return Appointment::query()
            ->with(['patient', 'clinic_user.user'])
            ->where('clinic_id', $this->clinicId)
            ->whereDate('schedule_date', $this->queueDate);
    }
}
