<?php

namespace App\Filament\Resources\EmrVisitResource\Pages;

use App\Filament\Resources\EmrVisitResource;
use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditEmrVisit extends EditRecord
{
    protected static string $resource = EmrVisitResource::class;
    protected ?string $maxContentWidth = 'full';

    public function getTitle(): string
    {
        return 'EMR Rawat Jalan';
    }

    public function getHeader(): ?View
    {
        $record = $this->getRecord()->loadMissing(['patient', 'clinic_user.user', 'clinic_user.clinic']);

        return view('filament.pages.outpatient-emr-header', [
            'record' => $record,
            'patient' => $record->patient,
            'clinic' => $record->clinic_user?->clinic,
            'doctor' => $record->clinic_user?->user,
            'backUrl' => PatientResource::getUrl('view', ['record' => $record->patient_id]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\Action::make('back')->label('Kembali ke riwayat pasien')->url(
            PatientResource::getUrl('view', ['record' => $this->getRecord()->patient_id]),
        )];
    }

    protected function getRedirectUrl(): string
    {
        return PatientResource::getUrl('view', ['record' => $this->getRecord()->patient_id]);
    }
}
