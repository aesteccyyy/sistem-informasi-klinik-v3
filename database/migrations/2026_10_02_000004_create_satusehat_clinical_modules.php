<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_member_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed');
            $table->string('relationship_code');
            $table->string('relationship_display');
            $table->string('condition_code');
            $table->string('condition_display');
            $table->string('onset_age')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('allergy_intolerances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('medication');
            $table->string('code_system')->default('http://snomed.info/sct');
            $table->string('code');
            $table->string('display');
            $table->string('clinical_status')->default('active');
            $table->string('verification_status')->default('unconfirmed');
            $table->string('criticality')->nullable();
            $table->string('reaction_code_system')->default('http://snomed.info/sct');
            $table->string('reaction_code')->nullable();
            $table->string('reaction_display')->nullable();
            $table->text('reaction_description')->nullable();
            $table->date('onset_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('medication_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medication_system')->default('http://sys-ids.kemkes.go.id/kfa');
            $table->string('medication_code');
            $table->string('medication_display');
            $table->string('status')->default('active');
            $table->text('dosage')->nullable();
            $table->date('effective_start')->nullable();
            $table->date('effective_end')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('satusehat_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('exam');
            $table->string('code_system')->default('http://loinc.org');
            $table->string('code');
            $table->string('display');
            $table->string('value_type')->default('quantity');
            $table->decimal('value_quantity', 12, 4)->nullable();
            $table->string('value_string')->nullable();
            $table->string('unit')->nullable();
            $table->string('unit_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clinical_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed');
            $table->text('investigation')->nullable();
            $table->text('summary');
            $table->string('prognosis_system')->default('http://terminology.kemkes.go.id/CodeSystem/clinical-term');
            $table->string('prognosis_code');
            $table->string('prognosis_display');
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('care_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('lifecycle_status')->default('active');
            $table->string('description_system')->default('http://snomed.info/sct');
            $table->string('description_code');
            $table->string('description_display');
            $table->date('start_date')->nullable();
            $table->string('target_detail')->nullable();
            $table->date('target_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('plan_type')->default('care');
            $table->string('status')->default('active');
            $table->string('intent')->default('plan');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('activity_description');
            $table->string('activity_status')->default('not-started');
            $table->string('scheduled_string')->nullable();
            $table->text('notes')->nullable();
            $table->string('satusehat_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plans');
        Schema::dropIfExists('care_goals');
        Schema::dropIfExists('clinical_impressions');
        Schema::dropIfExists('satusehat_observations');
        Schema::dropIfExists('medication_statements');
        Schema::dropIfExists('allergy_intolerances');
        Schema::dropIfExists('family_member_histories');
    }
};