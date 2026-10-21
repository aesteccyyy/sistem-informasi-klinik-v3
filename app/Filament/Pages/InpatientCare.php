<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\CareBed;
use App\Models\CareEncounter;
use App\Models\CareRoom;
use App\Models\Clinic;
use App\Models\Drug;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;
use Livewire\WithFileUploads;

class InpatientCare extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Rawat Inap';
    protected static ?string $title = 'Rawat Inap';
    protected static ?string $navigationGroup = 'Rawat Inap & IGD';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.inpatient-care';
    protected static string $careType = 'inpatient';
    protected static string $featureColumn = 'has_inpatient';
    protected static string $prefix = 'RI';
    protected static bool $settingsMode = false;
    protected ?string $heading = '';
    protected ?string $maxContentWidth = 'full';

    public ?int $clinicId = null;
    public ?int $encounterId = null;
    public string $search = '';
    public string $statusFilter = 'active';
    public string $activeSection = 'identitas';
    public array $clinicalNote = [];
    public array $vitals = [];
    public array $medication = [];
    public array $procedure = [];
    public string $supportingExamType = 'laboratory';
    public array $discharge = [];
    public array $roomForm = [];
    public array $bedForm = [];
    public array $transfer = [];
    public ?int $moveBedId = null;
    public array $administrationQuantities = [];

    public static function canAccess(): bool
    {
        return static::availableClinics()->isNotEmpty();
    }

    public function mount(): void
    {
        $this->clinicId = $this->clinics()->first()?->id;
        $this->clinicalNote = ['note_type' => 'clinical', 'noted_at' => now()->format('Y-m-d\TH:i')];
        $this->vitals = ['noted_at' => now()->format('Y-m-d\TH:i')];
        $this->medication = ['quantity' => 1];
        $this->discharge = ['discharged_at' => now()->format('Y-m-d\TH:i')];
        $requestedClinicId = (int) request()->query('clinic');
        if ($requestedClinicId && $this->clinics()->contains('id', $requestedClinicId)) {
            $this->clinicId = $requestedClinicId;
        }
        if (($requestedEncounterId = (int) request()->query('encounter')) > 0) {
            $encounter = $this->encounterQuery()->find($requestedEncounterId);
            if ($encounter) $this->selectEncounter($encounter->id);
        }
    }

    public function render(): View
    {
        $encounter = $this->selectedEncounter();

        return view(static::$view, [
            'clinics' => $this->clinics(),
            'encounters' => $this->encounters(),
            'encounter' => $encounter,
            'hasInitialMedicalAssessment' => $encounter?->notes->contains('note_type', 'initial_assessment') ?? false,
            'hasInitialNursingAssessment' => $encounter?->notes->contains('note_type', 'nursing_assessment') ?? false,
            'rooms' => $this->rooms(),
            'moveBeds' => static::$careType === 'inpatient' ? CareBed::query()->where('status', 'available')->whereHas('room', fn ($room) => $room->where('clinic_id', $this->validClinicId())->where('is_active', true))->with('room')->orderBy('care_room_id')->orderBy('code')->get() : collect(),
            'roomInventory' => static::$careType === 'inpatient' ? $this->roomInventory() : collect(),
            'transferRooms' => static::$careType === 'emergency' && $this->clinicHasInpatient() ? CareRoom::query()->where('clinic_id', $this->validClinicId())->where('is_active', true)->orderBy('name')->get() : collect(),
            'drugs' => $this->drugs(),
            'careTitle' => static::$careType === 'inpatient' ? 'Rawat Inap' : 'IGD',
            'carePrefix' => static::$prefix,
            'careType' => static::$careType,
            'settingsMode' => static::$settingsMode,
            'canEditMedical' => $this->isDoctor(),
            'canEditNursing' => $this->isNurse(),
            'canWriteVitals' => $this->isNurse(),
            'canOrderMedication' => $this->isDoctor(),
            'canAdministerMedication' => $this->isNurse() || $this->hasRole(RoleEnum::PHARMACIST->value),
            'canWriteProcedure' => $this->isDoctor() || $this->isNurse(),
            'canDischarge' => $this->isDoctor(),
            'canManageRoomSettings' => $this->canManageRoomSettings(),
        ]);
    }

    public function updatedClinicId(): void
    {
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId), 403);
        $this->encounterId = null;
    }

    public function updatedTransferCareRoomId(): void
    {
        $this->transfer['care_bed_id'] = null;
    }

    public function selectEncounter(int $encounterId): void
    {
        $encounter = $this->encounterQuery()->findOrFail($encounterId);
        $this->encounterId = $encounter->id;
        $this->activeSection = 'identitas';
        $this->discharge = ['discharged_at' => now()->format('Y-m-d\TH:i')];
    }

    public function saveClinicalNote(): void
    {
        $encounter = $this->activeEncounter();
        $noteType = $this->clinicalNote['note_type'] ?? 'clinical';
        $authorizedNoteType = ($this->isDoctor() && in_array($noteType, ['initial_assessment', 'clinical'], true))
            || ($this->isNurse() && in_array($noteType, ['nursing_assessment', 'nursing', 'nursing_progress'], true));
        abort_unless($authorizedNoteType, 403);
        $rules = [
            'clinicalNote.note_type' => ['required', 'in:initial_assessment,clinical,nursing_assessment,nursing,nursing_progress'],
            'clinicalNote.noted_at' => ['required', 'date'],
        ];

        if ($noteType === 'initial_assessment') {
            $rules += [
                'clinicalNote.main_complaint' => ['required', 'string', 'max:5000'],
                'clinicalNote.present_illness' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.past_history' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.family_history' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.allergies' => ['nullable', 'string', 'max:2000'],
                'clinicalNote.current_medications' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.surgery_history' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.chronic_disease' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.habits' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.general_condition' => ['nullable', 'string', 'max:2000'],
                'clinicalNote.consciousness' => ['nullable', 'string', 'max:80'],
                'clinicalNote.head_neck_exam' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.thorax_exam' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.abdomen_exam' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.extremity_exam' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.other_exam' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.fall_risk' => ['nullable', 'string', 'max:1000'],
                'clinicalNote.nutrition_screening' => ['nullable', 'string', 'max:1000'],
                'clinicalNote.pressure_injury_risk' => ['nullable', 'string', 'max:1000'],
                'clinicalNote.pain_screening' => ['nullable', 'string', 'max:1000'],
                'clinicalNote.other_screening' => ['nullable', 'string', 'max:2000'],
                'clinicalNote.psychological_status' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.family_support' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.special_needs' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.risk_follow_up' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.initial_diagnosis' => ['required', 'string', 'max:5000'],
                'clinicalNote.care_plan' => ['required', 'string', 'max:5000'],
            ];
            $hasHighRisk = collect(['fall_risk', 'nutrition_screening', 'pressure_injury_risk', 'pain_screening', 'other_screening'])
                ->contains(fn (string $field) => preg_match('/tinggi|high|berat|severe/i', (string) ($this->clinicalNote[$field] ?? '')) === 1);
            if ($hasHighRisk) {
                $rules['clinicalNote.risk_follow_up'] = ['required', 'string', 'max:5000'];
            }
        } elseif (in_array($noteType, ['nursing_assessment', 'nursing', 'nursing_progress'], true)) {
            $rules += [
                'clinicalNote.nursing_assessment' => [$noteType === 'nursing' ? 'nullable' : 'required', 'string', 'max:5000'],
                'clinicalNote.nursing_diagnosis' => [$noteType === 'nursing' ? 'required' : 'nullable', 'string', 'max:5000'],
                'clinicalNote.nursing_outcome' => [$noteType === 'nursing' ? 'required' : 'nullable', 'string', 'max:5000'],
                'clinicalNote.intervention_plan' => [$noteType === 'nursing' ? 'required' : 'nullable', 'string', 'max:5000'],
                'clinicalNote.implementation' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.implementation_at' => ['nullable', 'date'],
                'clinicalNote.evaluation' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.education' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.activity_needs' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.nutrition_needs' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.elimination_needs' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.assistance_needs' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.doctor_notification' => ['nullable', 'string', 'max:3000'],
                'clinicalNote.risk_follow_up' => ['nullable', 'string', 'max:5000'],
            ];
            if ($noteType === 'nursing_assessment') {
                $hasHighRisk = collect(['fall_risk', 'nutrition_screening', 'pressure_injury_risk', 'pain_screening', 'other_screening'])
                    ->contains(fn (string $field) => preg_match('/tinggi|high|berat|severe/i', (string) ($this->clinicalNote[$field] ?? '')) === 1);
                if ($hasHighRisk) $rules['clinicalNote.risk_follow_up'] = ['required', 'string', 'max:5000'];
            }
        } else {
            $rules += [
                'clinicalNote.subjective' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.objective' => ['nullable', 'string', 'max:5000'],
                'clinicalNote.assessment' => ['required', 'string', 'max:5000'],
                'clinicalNote.plan' => ['required', 'string', 'max:5000'],
            ];
        }

        $note = $this->validate($rules)['clinicalNote'];
        $observations = collect($note)
            ->except(['note_type', 'subjective', 'objective', 'assessment', 'plan', 'noted_at', 'main_complaint', 'initial_diagnosis', 'care_plan', 'nursing_assessment', 'nursing_diagnosis', 'intervention_plan'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
        $observations['author_profession'] = auth()->user()?->getRoleNames()->first() ?? 'Petugas';

        $noteData = [
            'author_id' => auth()->id(),
            'note_type' => $noteType,
            'subjective' => match ($noteType) {
                'initial_assessment' => $note['main_complaint'],
                'nursing_assessment', 'nursing', 'nursing_progress' => $note['nursing_assessment'] ?? null,
                default => $note['subjective'] ?? null,
            },
            'objective' => $noteType === 'clinical' ? ($note['objective'] ?? null) : null,
            'assessment' => match ($noteType) {
                'initial_assessment' => $note['initial_diagnosis'],
                'nursing' => $note['nursing_diagnosis'] ?? null,
                'nursing_assessment', 'nursing_progress' => null,
                default => $note['assessment'],
            },
            'plan' => match ($noteType) {
                'initial_assessment' => $note['care_plan'],
                'nursing' => $note['intervention_plan'] ?? null,
                'nursing_assessment', 'nursing_progress' => $note['implementation'] ?? null,
                default => $note['plan'],
            },
            'observations' => $observations,
            'noted_at' => $note['noted_at'],
        ];

        if (in_array($noteType, ['initial_assessment', 'nursing_assessment'], true)) {
            DB::transaction(function () use ($encounter, $noteType, $noteData): void {
                $lockedEncounter = CareEncounter::query()->lockForUpdate()->findOrFail($encounter->id);
                if ($lockedEncounter->notes()->where('note_type', $noteType)->exists()) {
                    throw ValidationException::withMessages([
                        $noteType === 'initial_assessment' ? 'clinicalNote.main_complaint' : 'clinicalNote.nursing_assessment'
                            => 'Asesmen awal untuk profesi ini sudah tersimpan. Gunakan catatan perkembangan untuk pencatatan berikutnya.',
                    ]);
                }

                $lockedEncounter->notes()->create($noteData);
            });
        } else {
            $encounter->notes()->create($noteData);
        }

        $this->clinicalNote = ['note_type' => $this->noteTypeForSection(), 'noted_at' => now()->format('Y-m-d\TH:i')];
        Notification::make()->title(match ($noteType) {
            'initial_assessment' => 'Asesmen awal tersimpan',
            'nursing' => 'Asuhan keperawatan tersimpan',
            default => 'Catatan SOAP tersimpan',
        })->success()->send();
    }

    public function saveVitals(): void
    {
        abort_unless($this->isNurse(), 403);
        $encounter = $this->activeEncounter();
        $data = $this->validate([
            'vitals.systolic' => ['nullable', 'integer', 'between:40,300'],
            'vitals.diastolic' => ['nullable', 'integer', 'between:20,200'],
            'vitals.pulse' => ['nullable', 'integer', 'between:20,250'],
            'vitals.respiratory_rate' => ['nullable', 'integer', 'between:4,80'],
            'vitals.temperature' => ['nullable', 'numeric', 'between:30,45'],
            'vitals.spo2' => ['nullable', 'integer', 'between:40,100'],
            'vitals.pain' => ['nullable', 'integer', 'between:0,10'],
            'vitals.consciousness' => ['nullable', 'in:A,V,P,U'],
            'vitals.weight' => ['nullable', 'numeric', 'between:0.5,500'],
            'vitals.height' => ['nullable', 'numeric', 'between:30,250'],
            'vitals.oxygen_device' => ['nullable', 'string', 'max:80'],
            'vitals.oxygen_flow' => ['nullable', 'numeric', 'between:0,100'],
            'vitals.noted_at' => ['required', 'date'],
        ]);
        $observations = collect($data['vitals'])->except('noted_at')->filter(fn ($value) => $value !== null && $value !== '')->all();
        if (!$observations) {
            throw ValidationException::withMessages(['vitals.systolic' => 'Isi minimal satu parameter tanda vital.']);
        }
        if (!empty($observations['weight']) && !empty($observations['height'])) {
            $heightInMeters = (float) $observations['height'] / 100;
            $observations['bmi'] = round((float) $observations['weight'] / ($heightInMeters ** 2), 1);
        }
        $observations['author_profession'] = auth()->user()?->getRoleNames()->first() ?? 'Petugas';
        $encounter->notes()->create(['author_id' => auth()->id(), 'note_type' => 'vital', 'observations' => $observations, 'noted_at' => $data['vitals']['noted_at']]);
        $this->vitals = ['noted_at' => now()->format('Y-m-d\TH:i')];
        Notification::make()->title('Tanda vital tersimpan')->success()->send();
    }

    public function orderMedication(): void
    {
        abort_unless($this->isDoctor(), 403);
        $encounter = $this->activeEncounter();
        $data = $this->validate([
            'medication.drug_id' => ['required', 'integer'],
            'medication.dosage_instruction' => ['required', 'string', 'max:255'],
            'medication.dose' => ['nullable', 'string', 'max:80'],
            'medication.route' => ['nullable', 'string', 'max:40'],
            'medication.frequency' => ['nullable', 'string', 'max:80'],
            'medication.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'medication.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'medication.notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $drug = Drug::query()->whereKey($data['medication']['drug_id'])->where('clinic_id', $this->clinicId)->firstOrFail();
        if (!$drug->in_stock || (int) $drug->stock < (int) $data['medication']['quantity']) {
            throw ValidationException::withMessages(['medication.quantity' => 'Stok obat tidak mencukupi. Periksa stok pada menu Obat / Medication.']);
        }
        $encounter->medications()->create($data['medication'] + ['ordered_by' => auth()->id(), 'status' => 'ordered']);
        $this->medication = ['quantity' => 1];
        Notification::make()->title('Obat ditambahkan ke instruksi perawatan')->success()->send();
    }

    public function administerMedication(int $medicationId, int $quantity): void
    {
        abort_unless($this->isNurse() || $this->hasRole(RoleEnum::PHARMACIST->value), 403);
        $encounter = $this->activeEncounter();
        abort_unless($quantity > 0, 422);
        DB::transaction(function () use ($encounter, $medicationId, $quantity): void {
            $medication = $encounter->medications()->whereKey($medicationId)->whereIn('status', ['ordered', 'partial'])->lockForUpdate()->firstOrFail();
            $remaining = (int) $medication->quantity - (int) $medication->administered_quantity;
            if ($quantity > $remaining) throw ValidationException::withMessages(['administrationQuantities.' . $medication->id => 'Jumlah pemberian melebihi sisa instruksi obat.']);
            $drug = Drug::query()->whereKey($medication->drug_id)->where('clinic_id', $this->clinicId)->lockForUpdate()->firstOrFail();
            if ((int) $drug->stock < $quantity) {
                throw ValidationException::withMessages(['administrationQuantities.' . $medication->id => 'Stok obat tidak mencukupi.']);
            }
            $before = (int) $drug->stock;
            $drug->stock = $before - $quantity;
            $drug->in_stock = $drug->stock > 0;
            $drug->save();
            DB::table('drug_stock_movements')->insert([
                'drug_id' => $drug->id, 'care_medication_id' => $medication->id, 'user_id' => auth()->id(),
                'movement_type' => 'inpatient_dispense', 'quantity' => $quantity, 'stock_before' => $before,
                'stock_after' => $drug->stock, 'notes' => 'Pemberian obat rawat inap/IGD ' . $encounter->encounter_number,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $administered = (int) $medication->administered_quantity + $quantity;
            $medication->update(['administered_quantity' => $administered, 'status' => $administered >= $medication->quantity ? 'administered' : 'partial', 'administered_by' => auth()->id(), 'administered_at' => now()]);
        });
        Notification::make()->title('Pemberian obat dicatat dan stok diperbarui')->success()->send();
    }

    public function addProcedure(): void
    {
        $encounter = $this->activeEncounter();
        $category = $this->activeSection === 'penunjang' ? $this->supportingExamType : 'procedure';
        abort_unless($this->isDoctor() || ($this->isNurse() && $category === 'procedure'), 403);
        abort_unless(in_array($category, ['procedure', 'laboratory', 'radiology'], true), 422);
        $this->procedure['category'] = $category;
        $data = $this->validate([
            'procedure.name' => ['required', 'string', 'max:255'],
            'procedure.notes' => ['nullable', 'string', 'max:5000'],
            'procedure.performed_at' => ['required', 'date'],
            'procedure.category' => ['required', 'in:procedure,laboratory,radiology'],
            'procedure.attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,dcm', 'max:20480'],
        ]);
        $file = $data['procedure']['attachment'] ?? null;
        unset($data['procedure']['attachment']);
        $attachmentPath = $file?->store('care-procedures/'.$encounter->clinic_id.'/'.$encounter->id, 'local');
        if ($file && !$attachmentPath) throw ValidationException::withMessages(['procedure.attachment' => 'File gagal disimpan. Silakan coba lagi.']);
        try {
            $encounter->procedures()->create($data['procedure'] + [
                'performed_by' => auth()->id(),
                'attachment_path' => $attachmentPath,
                'attachment_name' => $file?->getClientOriginalName(),
                'attachment_mime' => $file?->getMimeType(),
            ]);
        } catch (\Throwable $exception) {
            if ($attachmentPath) Storage::disk('local')->delete($attachmentPath);
            throw $exception;
        }
        $this->procedure = ['performed_at' => now()->format('Y-m-d\TH:i')];
        Notification::make()->title($category === 'procedure' ? 'Tindakan tersimpan' : 'Pemeriksaan penunjang tersimpan')->success()->send();
    }

    public function setSupportingExamType(string $type): void
    {
        abort_unless(in_array($type, ['laboratory', 'radiology'], true), 422);
        $this->supportingExamType = $type;
        $this->procedure['category'] = $type;
    }

    public function downloadProcedureAttachment(int $procedureId)
    {
        abort_unless($this->encounterId, 404);
        $encounter = $this->encounterQuery()->findOrFail($this->encounterId);
        $procedure = $encounter->procedures()->whereKey($procedureId)->firstOrFail();
        abort_unless($procedure->attachment_path && Storage::disk('local')->exists($procedure->attachment_path), 404);

        return Storage::disk('local')->download($procedure->attachment_path, $procedure->attachment_name ?: basename($procedure->attachment_path));
    }

    public function dischargeEncounter(): void
    {
        abort_unless($this->isDoctor(), 403);
        $encounter = $this->activeEncounter();
        $dischargeRules = [
            'discharge.discharged_at' => ['required', 'date', 'after_or_equal:' . $encounter->arrived_at->format('Y-m-d\TH:i')],
            'discharge.discharge_method' => ['required', 'in:Pulih,Dirujuk,Pulang paksa,Meninggal' . (static::$careType === 'emergency' ? ',Rawat Inap' : '')],
            'discharge.discharge_diagnosis' => ['required', 'string', 'max:255'],
            'discharge.discharge_instructions' => ['required', 'string', 'max:5000'],
            'discharge.discharge_medications' => ['nullable', 'string', 'max:5000'],
        ];
        if (static::$careType === 'inpatient') {
            $dischargeRules += [
                'discharge.discharge_history_summary' => ['required', 'string', 'max:5000'],
                'discharge.discharge_exam_summary' => ['required', 'string', 'max:5000'],
                'discharge.discharge_supporting_summary' => ['required', 'string', 'max:5000'],
                'discharge.discharge_treatment_summary' => ['required', 'string', 'max:5000'],
                'discharge.discharge_progress_summary' => ['required', 'string', 'max:5000'],
                'discharge.discharge_condition' => ['required', 'string', 'max:2000'],
                'discharge.discharge_red_flags' => ['required', 'string', 'max:5000'],
                'discharge.follow_up_instructions' => ['required', 'string', 'max:5000'],
            ];
        } else {
            $dischargeRules['discharge.follow_up_instructions'] = ['nullable', 'string', 'max:5000'];
        }
        $transferToInpatient = static::$careType === 'emergency' && ($this->discharge['discharge_method'] ?? null) === 'Rawat Inap';
        if ($transferToInpatient) {
            abort_unless($this->clinicHasInpatient(), 422);
            $dischargeRules['transfer.care_room_id'] = ['required', 'integer'];
            $dischargeRules['transfer.care_bed_id'] = ['required', 'integer'];
        }
        $data = $this->validate($dischargeRules);
        DB::transaction(function () use ($encounter, $data): void {
            $locked = CareEncounter::query()->whereKey($encounter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'active', 422);
            $transferToInpatient = static::$careType === 'emergency' && ($data['discharge']['discharge_method'] ?? null) === 'Rawat Inap';
            $transferRoom = null;
            if ($transferToInpatient) {
                if (CareEncounter::query()->where('clinic_id', $this->clinicId)->where('patient_id', $locked->patient_id)->where('care_type', 'inpatient')->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['discharge.discharge_method' => 'Pasien sudah memiliki admisi rawat inap aktif.']);
                }
                $transferRoom = CareRoom::query()->whereKey($data['transfer']['care_room_id'])->where('clinic_id', $this->clinicId)->where('is_active', true)->firstOrFail();
                $transferBed = CareBed::query()->whereKey($data['transfer']['care_bed_id'])->where('care_room_id', $transferRoom->id)->lockForUpdate()->firstOrFail();
                if ($transferBed->status !== 'available') throw ValidationException::withMessages(['transfer.care_bed_id' => 'Bed tersebut tidak lagi tersedia.']);
                $transferBed->update(['status' => 'occupied']);
                $clinic = Clinic::query()->whereKey($this->clinicId)->lockForUpdate()->firstOrFail();
                $sequence = CareEncounter::query()->where('clinic_id', $clinic->id)->whereDate('created_at', today())->count() + 1;
                CareEncounter::query()->create([
                    'clinic_id' => $clinic->id, 'patient_id' => $locked->patient_id, 'appointment_id' => $locked->appointment_id,
                    'clinic_user_id' => $locked->clinic_user_id, 'care_room_id' => $transferRoom->id, 'care_bed_id' => $transferBed->id,
                    'created_by' => auth()->id(), 'encounter_number' => 'RI-' . now()->format('Ymd') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                    'care_type' => 'inpatient', 'status' => 'active', 'arrived_at' => $data['discharge']['discharged_at'],
                    'admission_source' => 'IGD', 'admission_method' => 'Transfer internal', 'guarantor' => $locked->guarantor,
                    'initial_diagnosis' => $data['discharge']['discharge_diagnosis'], 'admission_reason' => $data['discharge']['discharge_instructions'],
                ]);
            }
            $locked->update([
                'status' => $transferToInpatient ? 'admitted' : 'discharged', 'discharged_at' => $data['discharge']['discharged_at'],
                'discharge_method' => $data['discharge']['discharge_method'],
                'discharge_diagnosis' => $data['discharge']['discharge_diagnosis'],
                'discharge_instructions' => $data['discharge']['discharge_instructions'],
                'discharge_medications' => $data['discharge']['discharge_medications'] ?? null,
                'follow_up_instructions' => $data['discharge']['follow_up_instructions'] ?? null,
                'discharge_history_summary' => $data['discharge']['discharge_history_summary'] ?? null,
                'discharge_exam_summary' => $data['discharge']['discharge_exam_summary'] ?? null,
                'discharge_supporting_summary' => $data['discharge']['discharge_supporting_summary'] ?? null,
                'discharge_treatment_summary' => $data['discharge']['discharge_treatment_summary'] ?? null,
                'discharge_progress_summary' => $data['discharge']['discharge_progress_summary'] ?? null,
                'discharge_condition' => $data['discharge']['discharge_condition'] ?? null,
                'discharge_red_flags' => $data['discharge']['discharge_red_flags'] ?? null,
                'discharge_signed_by' => auth()->id(),
                'discharge_signed_at' => now(),
            ]);
            if ($locked->care_bed_id) {
                CareBed::query()->whereKey($locked->care_bed_id)->where('status', 'occupied')->update(['status' => 'available']);
            }
        });
        Notification::make()->title($transferToInpatient ? 'Pasien dipindahkan ke Rawat Inap' : 'Pasien dipulangkan; status bed telah diperbarui')->success()->send();
    }

    public function addRoom(): void
    {
        abort_unless($this->canManageRoomSettings(), 403);
        $this->authorizeInpatientFeature();
        $data = $this->validate(['roomForm.code' => ['required', 'string', 'max:30', Rule::unique('care_rooms', 'code')->where('clinic_id', $this->validClinicId())], 'roomForm.name' => ['required', 'string', 'max:120'], 'roomForm.care_class' => ['nullable', 'string', 'max:40']]);
        CareRoom::query()->create(['clinic_id' => $this->validClinicId(), ...$data['roomForm'], 'is_active' => true]);
        $this->roomForm = [];
        Notification::make()->title('Ruang perawatan ditambahkan')->success()->send();
    }

    public function addBed(): void
    {
        abort_unless($this->canManageRoomSettings(), 403);
        $this->authorizeInpatientFeature();
        $data = $this->validate(['bedForm.care_room_id' => ['required', 'integer'], 'bedForm.code' => ['required', 'string', 'max:30']]);
        $room = CareRoom::query()->whereKey($data['bedForm']['care_room_id'])->where('clinic_id', $this->validClinicId())->firstOrFail();
        if ($room->beds()->where('code', $data['bedForm']['code'])->exists()) throw ValidationException::withMessages(['bedForm.code' => 'Nomor bed sudah digunakan pada ruang ini.']);
        $room->beds()->create(['code' => $data['bedForm']['code'], 'status' => 'available']);
        $this->bedForm = [];
        Notification::make()->title('Bed ditambahkan')->success()->send();
    }

    public function setBedMaintenance(int $bedId, bool $maintenance): void
    {
        abort_unless($this->canManageRoomSettings(), 403);
        $this->authorizeInpatientFeature();
        $bed = CareBed::query()->whereKey($bedId)->whereHas('room', fn ($room) => $room->where('clinic_id', $this->validClinicId()))->firstOrFail();
        abort_unless($bed->status !== 'occupied', 422);
        $bed->update(['status' => $maintenance ? 'maintenance' : 'available']);
        Notification::make()->title('Status bed diperbarui')->success()->send();
    }

    public function movePatientBed(): void
    {
        abort_unless($this->isNurse() || $this->hasRole(RoleEnum::ADMIN->value) || $this->hasRole(RoleEnum::SUPER_ADMIN->value), 403);
        $this->authorizeInpatientFeature();
        $encounter = $this->activeEncounter();
        abort_unless(static::$careType === 'inpatient', 403);
        $data = $this->validate(['moveBedId' => ['required', 'integer']]);
        DB::transaction(function () use ($encounter, $data): void {
            $lockedEncounter = CareEncounter::query()->whereKey($encounter->id)->lockForUpdate()->firstOrFail();
            $targetBed = CareBed::query()->whereKey($data['moveBedId'])->whereHas('room', fn ($room) => $room->where('clinic_id', $this->clinicId)->where('is_active', true))->lockForUpdate()->firstOrFail();
            if ($targetBed->status !== 'available') throw ValidationException::withMessages(['moveBedId' => 'Bed tersebut sudah tidak tersedia.']);
            if ($lockedEncounter->care_bed_id) {
                $oldBed = CareBed::query()->whereKey($lockedEncounter->care_bed_id)->lockForUpdate()->first();
                if ($oldBed?->status === 'occupied') $oldBed->update(['status' => 'available']);
            }
            $targetBed->update(['status' => 'occupied']);
            $lockedEncounter->update(['care_room_id' => $targetBed->care_room_id, 'care_bed_id' => $targetBed->id]);
        });
        $this->moveBedId = null;
        Notification::make()->title('Pasien dipindahkan ke bed baru')->success()->send();
    }

    public function selectSection(string $section): void
    {
        abort_unless(in_array($section, ['identitas', 'asesmen', 'keperawatan', 'vital', 'soap', 'obat', 'tindakan', 'penunjang', 'resume', 'ruang'], true), 404);
        $this->activeSection = $section;
        if (in_array($section, ['asesmen', 'keperawatan', 'soap'], true)) {
            $this->clinicalNote['note_type'] = $this->noteTypeForSection();
            $this->clinicalNote['noted_at'] ??= now()->format('Y-m-d\TH:i');
            if ($section === 'asesmen') {
                $encounter = $this->selectedEncounter();
                $this->clinicalNote['main_complaint'] ??= $encounter?->admission_reason;
                if ($this->isDoctor()) $this->clinicalNote['initial_diagnosis'] ??= $encounter?->initial_diagnosis;
            }
        }
        if ($section === 'penunjang') $this->procedure['category'] = $this->supportingExamType;
    }

    private function noteTypeForSection(): string
    {
        return match ($this->activeSection) {
            'asesmen' => $this->isDoctor() ? 'initial_assessment' : ($this->isNurse() ? 'nursing_assessment' : 'initial_assessment'),
            'keperawatan' => 'nursing',
            'soap' => $this->isDoctor() ? 'clinical' : ($this->isNurse() ? 'nursing_progress' : 'clinical'),
            default => 'clinical',
        };
    }

    public function currentPatient(): ?Patient
    {
        return $this->selectedEncounter()?->patient;
    }

    public function selectedEncounter(): ?CareEncounter
    {
        return $this->encounterId ? $this->encounterQuery()->with(['patient', 'room', 'bed', 'doctor.user', 'notes.author', 'medications.drug', 'procedures.performer'])->find($this->encounterId) : null;
    }

    public function encounters(): Collection
    {
        return $this->encounterQuery()
            ->with(['patient', 'room', 'bed'])
            ->when($this->statusFilter !== 'all', fn ($query) => $this->statusFilter === 'history' ? $query->whereIn('status', ['discharged', 'admitted']) : $query->where('status', 'active'))
            ->when(trim($this->search) !== '', fn ($query) => $query->whereHas('patient', fn ($patient) => $patient
                ->where('name', 'like', '%' . trim($this->search) . '%')
                ->orWhere('nik', 'like', '%' . trim($this->search) . '%')))
            ->latest('arrived_at')
            ->limit(100)
            ->get();
    }

    public function updatedStatusFilter(): void
    {
        abort_unless(in_array($this->statusFilter, ['active', 'history', 'all'], true), 422);
    }

    public function rooms(): Collection { return CareRoom::query()->where('clinic_id', $this->validClinicId())->where('is_active', true)->orderBy('name')->get(); }
    public function roomInventory(): Collection { return CareRoom::query()->where('clinic_id', $this->validClinicId())->withCount(['beds', 'beds as available_beds_count' => fn ($beds) => $beds->where('status', 'available'), 'beds as occupied_beds_count' => fn ($beds) => $beds->where('status', 'occupied')])->with('beds')->orderBy('name')->get(); }
    public function drugs(): Collection { return Drug::query()->where('clinic_id', $this->validClinicId())->where('in_stock', true)->where('stock', '>', 0)->orderBy('name')->get(); }

    protected function encounterQuery()
    {
        $this->authorizeFeature();
        return CareEncounter::query()->where('clinic_id', $this->clinicId)->where('care_type', static::$careType);
    }

    protected function activeEncounter(): CareEncounter
    {
        $encounter = $this->encounterQuery()->whereKey($this->encounterId)->where('status', 'active')->firstOrFail();
        return $encounter;
    }

    protected function validClinicId(): int
    {
        $this->authorizeFeature();
        return (int) $this->clinicId;
    }

    protected function hasRole(string $role): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->hasRole($role) || ($user->role?->value ?? $user->getRawOriginal('role')) === $role;
    }

    protected function isDoctor(): bool { return $this->hasRole(RoleEnum::DOCTOR->value); }
    protected function isNurse(): bool { return $this->hasRole(RoleEnum::NURSE->value); }

    protected function canManageRoomSettings(): bool
    {
        return $this->hasRole(RoleEnum::ADMIN->value) || $this->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    protected function authorizeFeature(): void
    {
        abort_unless($this->clinicId && $this->clinics()->contains('id', (int) $this->clinicId), 403);
    }

    protected function authorizeInpatientFeature(): void
    {
        abort_unless($this->clinics()->contains('id', (int) $this->clinicId) && Clinic::query()->whereKey($this->clinicId)->where('has_inpatient', true)->exists(), 403);
    }

    protected function clinicHasInpatient(): bool
    {
        return (bool) Clinic::query()->whereKey($this->clinicId)->where('has_inpatient', true)->value('has_inpatient');
    }

    public function clinics(): Collection { return static::availableClinics(); }

    public static function shouldRegisterNavigation(): bool { return false; }

    protected static function availableClinics(): Collection
    {
        $user = auth()->user();
        if (!$user) return collect();
        $query = Clinic::query()->where(static::$featureColumn, true)->orderBy('name');
        if (!$user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) $query->whereIn('clinics.id', $user->clinics()->select('clinics.id'));
        return $query->get();
    }
}
