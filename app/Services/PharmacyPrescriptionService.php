<?php

namespace App\Services;

use App\Models\Drug;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyPrescriptionService
{
    public function reject(Prescription $prescription, string $reason): Prescription
    {
        return DB::transaction(function () use ($prescription, $reason): Prescription {
            $prescription = Prescription::query()->lockForUpdate()->findOrFail($prescription->id);
            if (!in_array($prescription->pharmacy_status, ['ordered', 'verified'], true)) {
                throw ValidationException::withMessages(['status' => 'Hanya resep yang belum diserahkan dapat ditolak.']);
            }
            $prescription->forceFill([
                'pharmacy_status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'is_prepared' => false,
            ])->save();

            return $prescription->fresh(['drug', 'appointment.patient', 'appointment.clinic_doctor.user', 'appointment.clinic_doctor.clinic']);
        });
    }

    public function dispense(Prescription $prescription, int $quantity, ?string $note = null, ?string $kfaCode = null, ?string $kfaDisplay = null, bool $bypassVerification = false): Prescription
    {
        $updated = DB::transaction(function () use ($prescription, $quantity, $note, $kfaCode, $kfaDisplay): Prescription {
            $prescription = Prescription::query()->lockForUpdate()->findOrFail($prescription->id);
            if (!in_array($prescription->pharmacy_status, ['verified', 'partially_dispensed'], true)
                && !($bypassVerification && $prescription->pharmacy_status === 'ordered')) {
                throw ValidationException::withMessages(['status' => 'Resep harus diverifikasi sebelum diserahkan.']);
            }

            if ($bypassVerification && $prescription->pharmacy_status === 'ordered') {
                $prescription->reviewed_by = auth()->id();
                $prescription->reviewed_at = now();
                $prescription->is_prepared = true;
            }
            $remaining = (int) $prescription->quantity - (int) $prescription->dispensed_quantity;
            if ($quantity < 1 || $quantity > $remaining) {
                throw ValidationException::withMessages(['quantity' => "Jumlah harus antara 1 dan {$remaining}."]);
            }

            $drug = Drug::query()->lockForUpdate()->findOrFail($prescription->drug_id);
            if ($drug->clinic_id !== $prescription->appointment->clinic_doctor?->clinic_id) {
                throw ValidationException::withMessages(['drug_id' => 'Obat tidak berasal dari klinik pada appointment ini.']);
            }
            if ($drug->stock < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stok tersedia tidak mencukupi.']);
            }

            $before = (int) $drug->stock;
            $drug->stock = $before - $quantity;
            $drug->in_stock = $drug->stock > 0;
            $drug->save();
            $prescription->dispensed_quantity += $quantity;
            $prescription->pharmacy_status = $prescription->dispensed_quantity >= $prescription->quantity
                ? 'dispensed' : 'partially_dispensed';
            $prescription->pharmacy_note = $note ?: $prescription->pharmacy_note;
            $prescription->dispensed_kfa_code = $kfaCode ?: $drug->kfa_code;
            $prescription->dispensed_kfa_display = $kfaDisplay ?: $drug->kfa_display;
            $prescription->dispensed_by = auth()->id();
            $prescription->dispensed_at = now();
            $prescription->is_prepared = $prescription->pharmacy_status === 'dispensed';
            $prescription->save();
            DB::table('drug_stock_movements')->insert([
                'drug_id' => $drug->id, 'prescription_id' => $prescription->id, 'user_id' => auth()->id(),
                'movement_type' => 'dispense', 'quantity' => $quantity, 'stock_before' => $before,
                'stock_after' => $drug->stock, 'notes' => $note, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $prescription->fresh(['drug', 'appointment.patient', 'appointment.clinic_doctor.user', 'appointment.clinic_doctor.clinic']);
        });

        return $updated;
    }
}
