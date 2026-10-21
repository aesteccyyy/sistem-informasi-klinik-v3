<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatusEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\PrescriptionResource\Pages;
use App\Filament\Resources\PrescriptionResource\RelationManagers;
use App\Models\Drug;
use App\Models\Prescription;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use App\Services\PharmacyPrescriptionService;
use App\Services\Satusehat\SatusehatClient;
use App\Services\Satusehat\SatusehatMedicationSync;
use App\Services\Satusehat\KfaCatalogClient;
use Filament\Notifications\Notification;

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;

    protected static ?string $navigationIcon = 'tabler-prescription';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Farmasi';

    public static function getNavigationLabel(): string
    {
        return 'Prescription / Apotik';
    }

    public static function form(Form $form): Form
    {
        $record = $form->getRecord();
        $appointment = $record->appointment()->first();

        //dd($appointment);

        return $form
            ->schema([
                Section::make(__('Prescription Data'))
                    // ->label('Clinic\'s Basic Info')
                    ->description(__('Required Drug\'s data for the Prescription'))
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Placeholder::make('clinic')
                            ->label('Clinic')
                            ->content($appointment->clinic_user()->first()->clinic()->first()->name),
                        Forms\Components\Select::make('drug_id')
                            ->relationship(
                                name: 'drug',
                                titleAttribute: 'name',
                                // ignoreRecord: true,
                                modifyQueryUsing: fn (Builder $query) => $query->where('in_stock', true)->where('clinic_id', $appointment->clinic_user()->first()->clinic_id)
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} : {$record->sku}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->live()
                            ->helperText(__('Filtered by Related Clinic & Drug\'s Ready Stock only')),
                        Placeholder::make('drug_category')
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $category = ($drug) ? $drug->drug_category()->first()->name : '-';
                                return $category;
                            })
                            ->live(),
                        Placeholder::make('drug_stock')
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $stock = ($drug) ? $drug->stock : '-';
                                return $stock;
                            })
                            ->live(),
                        Forms\Components\TextArea::make('dosage_instruction')
                            ->label('Aturan pakai')
                            ->required()
                            ->helperText('Contoh: diminum sesudah makan. Tulis instruksi yang jelas untuk pasien.')
                            ->columnSpanFull(),
                        Forms\Components\TextArea::make('description')
                            ->label('Catatan resep')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('dose_value')->label('Dosis per kali minum')->numeric()->minValue(0.001),
                        Forms\Components\Select::make('dose_unit')->label('Satuan dosis')->options([
                            'mg' => 'mg', 'g' => 'g', 'mcg' => 'mcg', 'mL' => 'mL', 'U' => 'Unit (U)',
                        ])->required(),
                        Forms\Components\Select::make('frequency')->label('Frekuensi')->options([
                            'QD' => '1 kali sehari (QD)', 'BID' => '2 kali sehari (BID)', 'TID' => '3 kali sehari (TID)',
                            'QID' => '4 kali sehari (QID)', 'Q4H' => 'Setiap 4 jam (Q4H)', 'Q6H' => 'Setiap 6 jam (Q6H)',
                        ])->required(),
                        Forms\Components\Select::make('route')->label('Rute pemberian')->options([
                            'oral' => 'Oral', 'intravenous' => 'Intravena', 'topical' => 'Topikal',
                            'inhalation' => 'Inhalasi', 'ophthalmic' => 'Mata', 'rectal' => 'Rektal', 'vaginal' => 'Vaginal',
                        ])->required(),
                        Forms\Components\TextInput::make('duration_days')->label('Durasi (hari)')->numeric()->minValue(1),
                        Forms\Components\Select::make('quantity_unit')->label('Satuan jumlah')->options([
                            'TAB' => 'Tablet', 'CAP' => 'Kapsul', 'mL' => 'mL', 'L' => 'Liter', 'g' => 'Gram', 'mg' => 'mg',
                        ])->default('TAB')->required()->helperText('Tablet/kapsul memakai kode OrderableDrugForm; ukuran volume/berat memakai satuan UCUM.'),
                        Placeholder::make('pharmacy_status')->label('Status apotik')->content(fn ($record) => $record?->pharmacy_status ?? 'Menunggu apotik'),
                    ]),

                Section::make(__('Price Calculation'))
                    // ->label(__('Automatic calculation for the Treatment\'s Price'))
                    ->description(__('Automatic calculation for the Prescription\'s Price'))
                    ->columnSpan(1)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->required()
                            ->live()
                            ->numeric()
                            ->minValue(1),
                        Placeholder::make('drug_price')
                            ->label(__('Drug Price'))
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $price = ($drug) ? $drug->price : 0;
                                return Number::currency($price, 'IDR');
                            })
                            ->live(),
                        Forms\Components\TextInput::make('discount')
                            ->required()
                            ->live()
                            ->suffix('%')
                            ->numeric(),
                        Placeholder::make('amount')
                            ->label(__('Amount'))
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $price = ($drug) ? $drug->price : 0;
                                $q = $get('quantity');
                                $p = $price;
                                $d = $get('discount');
                                $a = $q * ($p - ($p * ($d / 100)));
                                return Number::currency($a, 'IDR');
                            })
                            ->live(),
                    ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user->hasRole(RoleEnum::PHARMACIST->value)) {
                    $clinicIds = $user->clinics()->pluck('clinics.id');
                    $query->select('prescriptions.*')
                        ->join('appointments', 'prescriptions.appointment_id', '=', 'appointments.id')
                        ->join('clinic_user', 'appointments.clinic_user_id', '=', 'clinic_user.id')
                        ->whereIn('clinic_user.clinic_id', $clinicIds);
                    return;
                }
                if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
                    $query->select('prescriptions.*');
                    $query->leftJoin('appointments', 'appointment_id', '=', 'appointments.id');
                    $query->leftJoin('clinic_user', 'clinic_user.id', '=', 'appointments.clinic_user_id');
                    $query->whereIn('clinic_user.clinic_id', $user->clinics()->pluck('clinics.id'));
                    $query->where('appointments.status', AppointmentStatusEnum::DIAGNOSED->value);
                    clock()->info($query->toRawSql());
                } else {
                    $query->select('prescriptions.*');
                    $query->leftJoin('appointments', 'appointment_id', '=', 'appointments.id');
                    $query->where('appointments.status', AppointmentStatusEnum::DIAGNOSED->value);
                    clock()->info($query->toRawSql());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('drug.sku')->label(__('SKU')),
                Tables\Columns\TextColumn::make('drug.name')->label(__('Drug')),
                Tables\Columns\TextColumn::make('drug.kfa_code')->label('KFA'),
                Tables\Columns\TextColumn::make('drug.drug_category.name')->label(__('Drug Category')),
                Tables\Columns\TextColumn::make('description')->label(__('Description'))->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dosage_instruction')->label('Instruksi dosis'),
                Tables\Columns\TextColumn::make('quantity')->label(__('Quantity')),
                Tables\Columns\TextColumn::make('dispensed_quantity')->label('Diserahkan'),
                Tables\Columns\TextColumn::make('pharmacy_status')->label('Status apotik')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ordered' => 'Menunggu apotik', 'verified' => 'Diverifikasi', 'partially_dispensed' => 'Diserahkan sebagian',
                        'dispensed' => 'Diserahkan', 'rejected' => 'Ditolak', default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'dispensed' => 'success', 'rejected' => 'danger', 'partially_dispensed' => 'warning', 'verified' => 'info', default => 'gray',
                    })->tooltip(fn (Prescription $record): ?string => $record->satusehat_sync_error),
                Tables\Columns\TextColumn::make('satusehat_sync_status')->label('SATUSEHAT')->badge()
                    ->color(fn (?string $state): string => $state === 'failed' ? 'danger' : (in_array($state, ['synced', 'dispensed', 'cancelled'], true) ? 'success' : 'gray'))
                    ->tooltip(fn (Prescription $record): ?string => $record->satusehat_sync_error),
                Tables\Columns\TextColumn::make('price')->label(__('Price'))->money('IDR'),
                Tables\Columns\TextColumn::make('discount')->label(__('Discount')),
                Tables\Columns\TextColumn::make('amount')->label(__('Amount'))->money('IDR'),
                Tables\Columns\IconColumn::make('is_prepared')->label(__('Is Prepared'))->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Prescription $record): bool => auth()->user()?->canManageMedication() && $record->pharmacy_status === 'ordered' && !$record->satusehat_medication_request_id),
                Tables\Actions\Action::make('verify')
                    ->label('Verifikasi resep')->icon('heroicon-o-clipboard-document-check')->color('info')
                    ->visible(fn (Prescription $record): bool => auth()->user()?->hasRole(RoleEnum::PHARMACIST->value) && $record->pharmacy_status === 'ordered')
                    ->action(function (Prescription $record, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        $record->setPrepare();
                        Notification::make()->title('Resep diverifikasi')->success()->send();
                        try {
                            $sync->syncRequest($record->fresh(), $client);
                        } catch (\Throwable $exception) {
                            $record->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                            Notification::make()->title('Resep diverifikasi, sinkronisasi SATUSEHAT gagal')->body($exception->getMessage())->warning()->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak resep')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                    ->visible(fn (Prescription $record): bool => auth()->user()?->hasRole(RoleEnum::PHARMACIST->value) && in_array($record->pharmacy_status, ['ordered', 'verified'], true))
                    ->form([Forms\Components\Textarea::make('rejection_reason')->label('Alasan penolakan')->required()])
                    ->action(function (Prescription $record, array $data, PharmacyPrescriptionService $service, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        $service->reject($record, $data['rejection_reason']);
                        if ($record->satusehat_medication_request_id) {
                            try {
                                $sync->cancelRequest($record, $client);
                            } catch (\Throwable $exception) {
                                $record->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                                Notification::make()->title('Resep ditolak; pembatalan SATUSEHAT gagal')->body($exception->getMessage())->warning()->send();
                                return;
                            }
                        }
                        Notification::make()->title('Resep ditolak')->success()->send();
                    }),
                Tables\Actions\Action::make('dispense')
                    ->label('Serahkan obat')->icon('heroicon-o-archive-box-arrow-down')->color('success')
                    ->visible(fn (Prescription $record): bool =>
                        (auth()->user()?->hasRole(RoleEnum::PHARMACIST->value)
                            && in_array($record->pharmacy_status, ['verified', 'partially_dispensed'], true))
                        || (auth()->user()?->hasRole(RoleEnum::DOCTOR->value)
                            && auth()->user()?->can_manage_medication
                            && in_array($record->pharmacy_status, ['ordered', 'verified', 'partially_dispensed'], true)))
                    ->form([
                        Forms\Components\TextInput::make('quantity')->label('Jumlah diserahkan')->numeric()->minValue(1)->required(),
                        Forms\Components\Select::make('dispensed_kfa_code')->label('Cari produk aktual KFA')
                            ->default(fn (Prescription $record): ?string => $record->drug?->kfa_code)
                            ->searchable()->live()
                            ->getSearchResultsUsing(fn (string $search): array => array_filter(
                                app(KfaCatalogClient::class)->searchMedicines($search),
                                fn (string $label, string $code): bool => str_starts_with($code, '93'),
                                ARRAY_FILTER_USE_BOTH,
                            ))
                            ->getOptionLabelUsing(fn ($value, Forms\Get $get): ?string => filled($get('dispensed_kfa_display')) ? $get('dispensed_kfa_display') . ' — KFA ' . $value : $value)
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $set('dispensed_kfa_display', filled($state) ? app(KfaCatalogClient::class)->findMedicineDisplay((string) $state) : null);
                            })
                            ->helperText('Pilih produk aktual berkode 93xxxxxx yang benar benar diserahkan. Pencarian membutuhkan akses API KFA.'),
                        Forms\Components\TextInput::make('dispensed_kfa_display')->label('Nama KFA produk aktual')->maxLength(255),
                        Forms\Components\Textarea::make('pharmacy_note')->label('Catatan apotik'),
                    ])
                    ->action(function (Prescription $record, array $data, PharmacyPrescriptionService $service, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        try {
                            $directDoctor = auth()->user()?->hasRole(RoleEnum::DOCTOR->value)
                                && auth()->user()?->can_manage_medication;
                            $updated = $service->dispense($record, (int) $data['quantity'], $data['pharmacy_note'] ?? null, $data['dispensed_kfa_code'] ?? null, $data['dispensed_kfa_display'] ?? null, $directDoctor);
                        } catch (\Throwable $exception) {
                            Notification::make()->title('Penyerahan gagal')->body($exception->getMessage())->danger()->send();
                            return;
                        }

                        try {
                            if (!$updated->satusehat_medication_request_id) $sync->syncRequest($updated, $client);
                            $sync->syncDispense($updated->fresh(), $client);
                            Notification::make()->title('Obat diserahkan dan tersinkron ke SATUSEHAT')->success()->send();
                        } catch (\Throwable $exception) {
                            $updated->refresh()->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                            Notification::make()->title('Penyerahan tersimpan; sinkronisasi perlu dicoba ulang')->body($exception->getMessage())->warning()->send();
                        }
                    }),
                Tables\Actions\Action::make('retryDispenseSync')
                    ->label('Ulangi sinkronisasi penyerahan')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (Prescription $record): bool => auth()->user()?->hasRole(RoleEnum::PHARMACIST->value) && in_array($record->pharmacy_status, ['partially_dispensed', 'dispensed'], true) && !$record->satusehat_medication_dispense_id)
                    ->action(function (Prescription $record, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        try {
                            if (!$record->satusehat_medication_request_id) $sync->syncRequest($record, $client);
                            $sync->syncDispense($record->fresh(), $client);
                            Notification::make()->title('Penyerahan berhasil disinkronkan')->success()->send();
                        } catch (\Throwable $exception) {
                            $record->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                            Notification::make()->title('Sinkronisasi gagal')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('syncRequest')
                    ->label('Kirim resep ke SATUSEHAT')->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (Prescription $record): bool => auth()->user()?->canManageMedication() && auth()->user()?->hasRole(RoleEnum::DOCTOR->value) && $record->pharmacy_status === 'ordered' && !$record->satusehat_medication_request_id)
                    ->action(function (Prescription $record, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        try {
                            $sync->syncRequest($record, $client);
                            Notification::make()->title('MedicationRequest berhasil dikirim')->success()->send();
                        } catch (\Throwable $exception) {
                            $record->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                            Notification::make()->title('Sinkronisasi gagal')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('retryRequestSync')
                    ->label('Ulangi sinkronisasi resep')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (Prescription $record): bool => auth()->user()?->hasRole(RoleEnum::PHARMACIST->value) && $record->pharmacy_status === 'verified' && !$record->satusehat_medication_request_id)
                    ->action(function (Prescription $record, SatusehatMedicationSync $sync, SatusehatClient $client): void {
                        try {
                            $sync->syncRequest($record, $client);
                            Notification::make()->title('MedicationRequest berhasil dikirim')->success()->send();
                        } catch (\Throwable $exception) {
                            $record->forceFill(['satusehat_sync_status' => 'failed', 'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->saveQuietly();
                            Notification::make()->title('Sinkronisasi gagal')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()->visible(fn (Prescription $record): bool => auth()->user()?->hasRole(RoleEnum::DOCTOR->value) && $record->pharmacy_status === 'ordered' && !$record->satusehat_medication_request_id),
            ])
            ->bulkActions([])
            ->defaultGroup('appointment.id');
        // ->groups([
        //     Group::make('appointment.id')
        //         ->label('Appointment'),
        // ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePrescriptions::route('/'),
        ];
    }
}
