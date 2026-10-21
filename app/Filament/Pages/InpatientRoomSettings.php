<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\Clinic;
use Illuminate\Support\Collection;

class InpatientRoomSettings extends InpatientCare
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Pengaturan Kamar & Bed';
    protected static ?string $title = 'Pengaturan Kamar & Bed';
    protected static ?string $navigationGroup = 'Administrasi Klinik';
    protected static ?int $navigationSort = 3;
    protected static bool $settingsMode = true;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function mount(): void
    {
        parent::mount();
        $this->activeSection = 'ruang';
    }

    protected static function availableClinics(): Collection
    {
        $user = auth()->user();
        if (!$user) return collect();

        $query = Clinic::query()->orderBy('name');
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
            $query->whereIn('clinics.id', $user->clinics()->select('clinics.id'));
        }

        return $query->get();
    }

    protected function authorizeInpatientFeature(): void
    {
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);
    }
}
