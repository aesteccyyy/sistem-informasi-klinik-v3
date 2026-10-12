<?php

namespace App\Filament\Resources\EmrVisitResource\Pages;

use App\Filament\Resources\EmrVisitResource;
use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmrVisit extends EditRecord
{
    protected static string $resource = EmrVisitResource::class;

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
