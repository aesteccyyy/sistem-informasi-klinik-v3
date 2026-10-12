<?php

namespace App\Filament\Resources\TreatmentServiceResource\Pages;

use App\Filament\Resources\TreatmentServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTreatmentService extends EditRecord
{
    protected static string $resource = TreatmentServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}