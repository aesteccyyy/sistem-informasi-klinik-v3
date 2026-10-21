<?php

namespace App\Filament\Resources;

use App\Jobs\Satusehat\SyncPatientToSatusehat;
use App\Models\Patient;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Throwable;

class SatusehatPatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'tabler-cloud-upload';

    protected static ?string $navigationLabel = 'Kirim Data Pasien';

    protected static ?string $modelLabel = 'pasien';

    protected static ?string $pluralModelLabel = 'kirim data pasien';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Integrasi SATUSEHAT';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Pasien')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable(),
                Tables\Columns\TextColumn::make('birth_date')->label('Tanggal Lahir')->date()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Telepon')->searchable(),
                Tables\Columns\TextColumn::make('satusehat_patient_id')
                    ->label('IHS ID')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('satusehat_sync_status')
                    ->label('Status Kirim')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'synced' => 'Terkirim',
                        'linked' => 'Terhubung',
                        'syncing' => 'Sedang dikirim',
                        'failed' => 'Gagal',
                        default => 'Belum dikirim',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'synced' => 'success',
                        'linked', 'syncing' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (Patient $record): ?string => $record->satusehat_sync_error),
                Tables\Columns\TextColumn::make('satusehat_synced_at')
                    ->label('Terakhir dikirim')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('satusehat_sync_status')
                    ->label('Status kirim')
                    ->options([
                        'pending' => 'Belum dikirim',
                        'syncing' => 'Sedang dikirim',
                        'synced' => 'Terkirim',
                        'linked' => 'Terhubung',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('send_to_satusehat')
                    ->label('Kirim pasien terpilih')
                    ->icon('tabler-cloud-upload')
                    ->color('info')
                    ->visible(fn (): bool => auth()->user()?->can('update', Patient::class) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Kirim data pasien ke SATUSEHAT?')
                    ->modalDescription('Patient, data klinis pasien, dan seluruh appointment terkait akan dikirim satu per satu.')
                    ->modalSubmitActionLabel('Kirim')
                    ->action(function (Collection $records): void {
                        $sent = 0;
                        $failed = 0;

                        foreach ($records as $patient) {
                            try {
                                SyncPatientToSatusehat::dispatchSync($patient->getKey());
                                $patient->refresh();

                                if ($patient->satusehat_sync_status !== 'synced') {
                                    $failed++;
                                } else {
                                    $sent++;
                                }
                            } catch (Throwable) {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title('Proses kirim SATUSEHAT selesai')
                            ->body("{$sent} pasien berhasil diproses; {$failed} gagal.")
                            ->color($failed > 0 ? 'warning' : 'success')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => SatusehatPatientResource\Pages\ListSatusehatPatients::route('/'),
        ];
    }
}
