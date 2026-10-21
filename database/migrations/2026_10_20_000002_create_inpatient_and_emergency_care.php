<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('care_class', 40)->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['clinic_id', 'code']);
        });

        Schema::create('care_beds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_room_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('status', 20)->default('available')->index();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['care_room_id', 'code']);
        });

        Schema::create('care_encounters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_user_id')->nullable()->constrained('clinic_user')->nullOnDelete();
            $table->foreignId('care_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('care_bed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('encounter_number', 40)->unique();
            $table->string('care_type', 20)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('arrived_at')->index();
            $table->string('admission_source', 40)->nullable();
            $table->string('admission_method', 40)->nullable();
            $table->string('guarantor')->nullable();
            $table->string('initial_diagnosis')->nullable();
            $table->text('admission_reason')->nullable();
            $table->string('triage_level', 30)->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->string('discharge_method', 40)->nullable();
            $table->string('discharge_diagnosis')->nullable();
            $table->text('discharge_instructions')->nullable();
            $table->text('discharge_medications')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->text('discharge_history_summary')->nullable();
            $table->text('discharge_exam_summary')->nullable();
            $table->text('discharge_supporting_summary')->nullable();
            $table->text('discharge_treatment_summary')->nullable();
            $table->text('discharge_progress_summary')->nullable();
            $table->text('discharge_condition')->nullable();
            $table->text('discharge_red_flags')->nullable();
            $table->foreignId('discharge_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('discharge_signed_at')->nullable();
            $table->timestamps();
            $table->index(['clinic_id', 'care_type', 'status']);
        });

        Schema::create('care_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note_type', 24)->index();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->json('observations')->nullable();
            $table->timestamp('noted_at')->index();
            $table->timestamps();
        });

        Schema::create('care_medications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->restrictOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dosage_instruction')->nullable();
            $table->string('dose')->nullable();
            $table->string('route', 40)->nullable();
            $table->string('frequency', 80)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('administered_quantity')->default(0);
            $table->string('status', 20)->default('ordered')->index();
            $table->timestamp('administered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('care_procedures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('drug_stock_movements', function (Blueprint $table): void {
            $table->foreignId('care_medication_id')->nullable()->after('prescription_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drug_stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('care_medication_id');
        });
        Schema::dropIfExists('care_procedures');
        Schema::dropIfExists('care_medications');
        Schema::dropIfExists('care_notes');
        Schema::dropIfExists('care_encounters');
        Schema::dropIfExists('care_beds');
        Schema::dropIfExists('care_rooms');
    }
};
