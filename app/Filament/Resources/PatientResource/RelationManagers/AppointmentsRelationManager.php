<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\EmrVisitResource;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Riwayat Kunjungan';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visit_number')
                    ->label('Nomor Kunjungan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule_date')
                    ->label('Tanggal Kunjungan')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('symptoms')
                    ->label('Status EMR')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Terisi' : 'Belum diisi')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->defaultSort('schedule_date', 'desc')
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('open_emr')
                    ->label('Buka EMR')
                    ->url(fn ($record): string => EmrVisitResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
