<?php

namespace App\Filament\Resources\TreatmentServiceResource\Pages;

use App\Filament\Resources\TreatmentServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTreatmentServices extends ListRecords
{
    protected static string $resource = TreatmentServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}