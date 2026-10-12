<?php

namespace App\Filament\Widgets;

use App\Enums\RoleEnum;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PatientVisitsChart extends ChartWidget
{
    protected static ?string $heading = 'Kunjungan Pasien (7 Hari Terakhir)';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = today()->subDays(6);
        $end = today();
        $query = Appointment::query()
            ->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()]);

        $user = auth()->user();
        if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
            $clinicIds = $user->clinics()->select('clinics.id');
            $query->whereHas('clinic_doctor', fn ($doctor) => $doctor->whereIn('clinic_id', $clinicIds));
        }

        $visits = $query
            ->selectRaw('schedule_date, COUNT(*) as total')
            ->groupBy('schedule_date')
            ->pluck('total', 'schedule_date');

        $labels = [];
        $values = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = Carbon::parse($key)->locale('id')->translatedFormat('D, d/m');
            $values[] = (int) ($visits[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah kunjungan',
                    'data' => $values,
                    'backgroundColor' => '#2563eb',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
