<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table): void {
            $table->string('kfa_code', 32)->nullable()->index();
            $table->string('kfa_display')->nullable();
            $table->string('kfa_system')->default('http://sys-ids.kemkes.go.id/kfa');
            $table->string('dosage_form')->nullable();
            $table->string('dosage_form_code', 32)->nullable();
            $table->string('dosage_form_system')->default('http://terminology.kemkes.go.id/CodeSystem/medication-form');
            $table->string('satusehat_medication_id')->nullable()->index();
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->text('dosage_instruction')->nullable();
            $table->decimal('dose_value', 10, 3)->nullable();
            $table->string('dose_unit', 32)->nullable();
            $table->string('frequency')->nullable();
            $table->string('route')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('quantity_unit', 32)->default('TAB');
            $table->string('dispensed_kfa_code', 32)->nullable();
            $table->string('dispensed_kfa_display')->nullable();
            $table->string('pharmacy_status', 24)->default('ordered')->index();
            $table->unsignedInteger('dispensed_quantity')->default(0);
            $table->text('pharmacy_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('satusehat_medication_request_id')->nullable()->index();
            $table->string('satusehat_medication_dispense_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
        });

        Schema::create('drug_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('drug_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 24);
            $table->integer('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['drug_id', 'created_at']);
        });

        // Before this migration, prescriptions deducted stock when they were saved.
        // Preserve that historical balance and prevent a second deduction on dispensing.
        DB::table('prescriptions')->where('pharmacy_status', 'ordered')->update([
            'pharmacy_status' => 'dispensed',
            'dispensed_quantity' => DB::raw('quantity'),
            'dispensed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_stock_movements');

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['dispensed_by']);
            $table->dropColumn([
                'dosage_instruction', 'dose_value', 'dose_unit', 'frequency', 'route', 'duration_days', 'quantity_unit', 'dispensed_kfa_code', 'dispensed_kfa_display',
                'pharmacy_status', 'dispensed_quantity', 'pharmacy_note', 'reviewed_by', 'reviewed_at',
                'dispensed_by', 'dispensed_at', 'rejection_reason',
                'satusehat_medication_request_id', 'satusehat_medication_dispense_id',
                'satusehat_sync_status', 'satusehat_sync_error', 'satusehat_synced_at',
            ]);
        });

        Schema::table('drugs', function (Blueprint $table): void {
            $table->dropIndex(['kfa_code']);
            $table->dropIndex(['satusehat_medication_id']);
            $table->dropColumn(['kfa_code', 'kfa_display', 'kfa_system', 'dosage_form', 'dosage_form_code', 'dosage_form_system', 'satusehat_medication_id']);
        });
    }
};
