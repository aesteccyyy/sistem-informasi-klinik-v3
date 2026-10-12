<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Clinic;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueueBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Panggilan Antrian';

    protected static ?string $title = 'Panggilan Antrian';

    protected static ?string $navigationGroup = 'Registration Counter';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.queue-board';

    public ?int $clinicId = null;

    public string $queueDate;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Appointment::class) ?? false;
    }

    public function mount(): void
    {
        $this->queueDate = today()->toDateString();
        $this->clinicId = $this->clinics()->first()?->id;
    }

    public function updatedClinicId(): void
    {
        $this->selectedClinic();
    }

    public function updatedQueueDate(): void
    {
        $this->validate(['queueDate' => ['required', 'date_format:Y-m-d']]);
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
        if (!$this->validQueueFilter()) {
            return null;
        }

        return $this->queueQuery()
            ->where('queue_status', QueueStatusEnum::CALLED->value)
            ->orderByDesc('queue_called_at')
            ->first();
    }

    public function waitingAppointments(): Collection
    {
        if (!$this->validQueueFilter()) {
            return collect();
        }

        return $this->queueQuery()
            ->where('queue_status', QueueStatusEnum::WAITING->value)
            ->orderBy('queue_number')
            ->limit(20)
            ->get();
    }

    public function queueStats(): array
    {
        if (!$this->validQueueFilter()) {
            return ['waiting' => 0, 'served' => 0, 'skipped' => 0];
        }

        $counts = $this->queueQuery()
            ->selectRaw('queue_status, COUNT(*) as total')
            ->groupBy('queue_status')
            ->pluck('total', 'queue_status');

        return [
            'waiting' => (int) ($counts[QueueStatusEnum::WAITING->value] ?? 0),
            'served' => (int) ($counts[QueueStatusEnum::SERVED->value] ?? 0),
            'skipped' => (int) ($counts[QueueStatusEnum::SKIPPED->value] ?? 0),
        ];
    }

    public function callNext(): void
    {
        $this->authorizeQueueManagement();
        $clinic = $this->selectedClinic();
        $this->validate(['queueDate' => ['required', 'date_format:Y-m-d']]);
        if (!$this->isToday()) {
            Notification::make()->title('Panggilan hanya dapat dilakukan untuk tanggal hari ini')->warning()->send();
            return;
        }

        $next = DB::transaction(function () use ($clinic): ?Appointment {
            Clinic::query()->whereKey($clinic->id)->lockForUpdate()->firstOrFail();

            $current = $this->queueQuery()
                ->where('queue_status', QueueStatusEnum::CALLED->value)
                ->orderByDesc('queue_called_at')
                ->lockForUpdate()
                ->first();
            $current?->forceFill(['queue_status' => QueueStatusEnum::SERVED->value])->saveQuietly();

            $next = $this->queueQuery()
                ->where('queue_status', QueueStatusEnum::WAITING->value)
                ->orderBy('queue_number')
                ->lockForUpdate()
                ->first();

            if ($next) {
                $next->forceFill([
                    'queue_status' => QueueStatusEnum::CALLED->value,
                    'queue_called_at' => now(),
                ])->saveQuietly();
            }

            return $next;
        });

        if (!$next) {
            Notification::make()->title('Tidak ada antrian menunggu')->warning()->send();
            return;
        }

        $this->dispatch('queue-called', code: $next->queue_code, patient: $next->patient?->name);
        Notification::make()->title("Memanggil antrian {$next->queue_code}")->success()->send();
    }

    public function recallCurrent(): void
    {
        $this->authorizeQueueManagement();
        $this->selectedClinic();
        if (!$this->isToday()) {
            Notification::make()->title('Panggilan hanya dapat dilakukan untuk tanggal hari ini')->warning()->send();
            return;
        }
        $current = $this->currentAppointment();

        if (!$current) {
            Notification::make()->title('Belum ada antrian yang sedang dipanggil')->warning()->send();
            return;
        }

        $current->forceFill(['queue_called_at' => now()])->saveQuietly();
        $this->dispatch('queue-called', code: $current->queue_code, patient: $current->patient?->name);
        Notification::make()->title("Memanggil ulang {$current->queue_code}")->success()->send();
    }

    public function skipCurrent(): void
    {
        $this->authorizeQueueManagement();
        $clinic = $this->selectedClinic();
        if (!$this->isToday()) {
            Notification::make()->title('Antrian hanya dapat dilewati untuk tanggal hari ini')->warning()->send();
            return;
        }

        $current = DB::transaction(function () use ($clinic): ?Appointment {
            Clinic::query()->whereKey($clinic->id)->lockForUpdate()->firstOrFail();

            $current = $this->queueQuery()
                ->where('queue_status', QueueStatusEnum::CALLED->value)
                ->orderByDesc('queue_called_at')
                ->lockForUpdate()
                ->first();
            $current?->forceFill(['queue_status' => QueueStatusEnum::SKIPPED->value])->saveQuietly();

            return $current;
        });

        if ($current) {
            Notification::make()->title("Antrian {$current->queue_code} dilewati")->warning()->send();
        }
    }

    public function canManageQueue(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])
            || $user->hasPermission('update_appointment'));
    }

    public function isToday(): bool
    {
        return $this->queueDate === today()->toDateString();
    }

    private function authorizeQueueManagement(): void
    {
        abort_unless($this->canManageQueue(), 403);
    }

    private function selectedClinic(): Clinic
    {
        $clinic = $this->clinics()->firstWhere('id', (int) $this->clinicId);
        abort_unless($clinic, 403);

        return $clinic;
    }

    private function validQueueFilter(): bool
    {
        if (!$this->clinicId || !$this->queueDate) {
            return false;
        }

        $this->selectedClinic();
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
