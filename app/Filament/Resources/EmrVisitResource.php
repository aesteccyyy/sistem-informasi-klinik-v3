<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatusEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\AppointmentResource\RelationManagers\PrescriptionsRelationManager;
use App\Filament\Resources\AppointmentResource\RelationManagers\TreatmentsRelationManager;
use App\Filament\Resources\EmrVisitResource\Pages;
use App\Filament\Resources\EmrResource;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmrVisitResource extends Resource
{
    protected static ?string $model = Appointment::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pasien dan Appointment')->columns(3)->schema([
                Forms\Components\Placeholder::make('patient_name')->label('Pasien')->content(fn (?Appointment $record): string => $record?->patient?->name ?? '-'),
                Forms\Components\Placeholder::make('patient_nik')->label('NIK')->content(fn (?Appointment $record): string => $record?->patient?->nik ?? '-'),
                Forms\Components\Placeholder::make('patient_phone')->label('Telepon')->content(fn (?Appointment $record): string => $record?->patient?->phone ?? '-'),
                Forms\Components\Placeholder::make('visit_number')->label('Nomor RM Kunjungan')->content(fn (?Appointment $record): string => $record?->visit_number ?? '-'),
                Forms\Components\Placeholder::make('schedule_date')->label('Tanggal')->content(fn (?Appointment $record): string => $record?->schedule_date?->format('d/m/Y') ?? '-'),
                Forms\Components\Placeholder::make('doctor')->label('Dokter')->content(fn (?Appointment $record): string => $record?->clinic_user?->user?->name ?? '-'),
            ]),
            Section::make('Pemeriksaan Fisik')->columns(3)->schema([
                Forms\Components\TextInput::make('height')->label('Tinggi Badan (cm)')->numeric()->minValue(1)->required(),
                Forms\Components\TextInput::make('weight')->label('Berat Badan (kg)')->numeric()->minValue(1)->required(),
                Forms\Components\TextInput::make('blood_pressure')->label('Tekanan Darah')->placeholder('120/80')->required(),
            ]),
            Forms\Components\Section::make('SOAP')->columns(2)->schema([
                Forms\Components\Textarea::make('symptoms')->label('S - Subjective / Keluhan')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('objective')->label('O - Objective / Pemeriksaan')->columnSpanFull(),
                Forms\Components\Textarea::make('diagnostic')->label('A - Assessment / Diagnosis')->columnSpanFull(),
                Forms\Components\Textarea::make('plan')->label('P - Plan / Rencana')->columnSpanFull(),
            ]),
            Section::make('Diagnosis SATUSEHAT')->schema([
                Forms\Components\Select::make('satusehat_diagnosis_code')
                    ->label('Kode Diagnosis ICD-10')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => \App\Models\Icd10Code::query()
                        ->where(fn (Builder $query) => $query
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%"))
                        ->orderBy('code')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($diagnosis): array => [$diagnosis->code => $diagnosis->display])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Icd10Code::query()->find($value)?->display)
                    ->native(false)
                    ->placeholder('Cari kode atau nama diagnosis ICD-10'),
            ]),
            Section::make('Diagnosis Klinis')->schema([
                Forms\Components\Repeater::make('clinicalImpressions')->relationship()->schema([
                    Forms\Components\Select::make('status')->options(['in-progress' => 'Dalam proses', 'completed' => 'Selesai', 'entered-in-error' => 'Salah input'])->default('completed')->required(),
                    Forms\Components\TextInput::make('investigation_system')->default('http://snomed.info/sct')->required(),
                    Forms\Components\TextInput::make('investigation_code')->label('Kode SNOMED CT')->required(),
                    Forms\Components\TextInput::make('investigation_display')->label('Pemeriksaan')->required(),
                    Forms\Components\Textarea::make('investigation')->label('Catatan pemeriksaan')->columnSpanFull(),
                    Forms\Components\Textarea::make('summary')->label('Ringkasan')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('prognosis_system')->default('http://terminology.kemkes.go.id/CodeSystem/clinical-term')->required(),
                    Forms\Components\TextInput::make('prognosis_code')->label('Kode prognosis')->required(),
                    Forms\Components\TextInput::make('prognosis_display')->label('Prognosis')->required(),
                ])->columns(2)->defaultItems(0)->addActionLabel('Tambah diagnosis klinis'),
            ]),
            Section::make('Target Perawatan')->schema([
                Forms\Components\Repeater::make('careGoals')->relationship()->schema([
                    Forms\Components\Select::make('lifecycle_status')->options(['proposed' => 'Diusulkan', 'planned' => 'Direncanakan', 'accepted' => 'Disetujui', 'active' => 'Aktif', 'on-hold' => 'Ditunda', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])->default('active')->required(),
                    Forms\Components\TextInput::make('description_system')->default('http://snomed.info/sct')->required(),
                    Forms\Components\TextInput::make('description_code')->label('Kode SNOMED CT')->required(),
                    Forms\Components\TextInput::make('description_display')->label('Target')->required(),
                    Forms\Components\DatePicker::make('start_date')->label('Tanggal mulai'),
                    Forms\Components\TextInput::make('target_detail')->label('Nilai target'),
                    Forms\Components\DatePicker::make('target_due_date')->label('Target selesai'),
                    Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                ])->columns(2)->defaultItems(0)->addActionLabel('Tambah target perawatan'),
            ]),
            Section::make('Rencana dan Instruksi Perawatan')->schema([
                Forms\Components\Repeater::make('carePlans')->relationship()->schema([
                    Forms\Components\Select::make('plan_type')->options(['care' => 'Rencana perawatan', 'medication_instruction' => 'Instruksi obat', 'nursing_instruction' => 'Instruksi keperawatan'])->required(),
                    Forms\Components\Select::make('status')->options(['draft' => 'Draft', 'active' => 'Aktif', 'on-hold' => 'Ditunda', 'revoked' => 'Dicabut', 'completed' => 'Selesai'])->default('active')->required(),
                    Forms\Components\Select::make('intent')->options(['proposal' => 'Usulan', 'plan' => 'Rencana', 'order' => 'Instruksi'])->default('plan')->required(),
                    Forms\Components\TextInput::make('title')->label('Judul')->required(),
                    Forms\Components\Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
                    Forms\Components\Textarea::make('activity_description')->label('Aktivitas')->required()->columnSpanFull(),
                    Forms\Components\Select::make('activity_status')->options(['not-started' => 'Belum dimulai', 'scheduled' => 'Terjadwal', 'in-progress' => 'Berlangsung', 'on-hold' => 'Ditunda', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])->default('not-started')->required(),
                    Forms\Components\TextInput::make('scheduled_string')->label('Jadwal'),
                    Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                ])->columns(2)->defaultItems(0)->addActionLabel('Tambah rencana/instruksi'),
            ]),
            Section::make('Status Kunjungan')->schema([
                Radio::make('status')->options([
                    AppointmentStatusEnum::SCHEDULED->value => Str::upper(AppointmentStatusEnum::SCHEDULED->label()),
                    AppointmentStatusEnum::DIAGNOSED->value => Str::upper(AppointmentStatusEnum::DIAGNOSED->label()),
                    AppointmentStatusEnum::PREPARED->value => Str::upper(AppointmentStatusEnum::PREPARED->label()),
                    AppointmentStatusEnum::CONFIRMED->value => Str::upper(AppointmentStatusEnum::CONFIRMED->label()),
                ])->descriptions([
                    AppointmentStatusEnum::SCHEDULED->value => 'Menunggu pemeriksaan',
                    AppointmentStatusEnum::DIAGNOSED->value => 'Pemeriksaan klinis selesai',
                    AppointmentStatusEnum::PREPARED->value => 'Resep siap diserahkan',
                    AppointmentStatusEnum::CONFIRMED->value => 'Kunjungan selesai dan tagihan dibuat',
                ])->required()->live(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return EmrResource::table($table);
    }

    public static function getRelations(): array
    {
        return [
            TreatmentsRelationManager::class,
            PrescriptionsRelationManager::class,
        ];
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
            return true;
        }

        return $user->hasPermission('update_appointment')
            && $user->clinics()->whereKey($record->clinic_user?->clinic_id)->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmrVisits::route('/'),
            'edit' => Pages\EditEmrVisit::route('/{record}/edit'),
        ];
    }
}
