<?php

use App\Models\AllergyIntolerance;
use App\Models\AnamnesisCondition;
use App\Models\Appointment;
use App\Models\CareGoal;
use App\Models\CarePlan;
use App\Models\ClinicalImpression;
use App\Models\FamilyMemberHistory;
use App\Models\MedicationStatement;
use App\Models\Patient;
use App\Models\SatusehatObservation;
use App\Models\User;
use App\Services\Satusehat\SatusehatClinicalDataSync;

it('maps patient anamnesis records to their SATUSEHAT FHIR resources', function () {
    $service = app(SatusehatClinicalDataSync::class);
    $patient = new Patient();
    $patient->satusehat_patient_id = 'PTEST';

    $invoke = function (string $method, array $arguments) use ($service): array {
        $reflection = new \ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($service, $arguments);
    };

    $familyHistory = $invoke('familyMemberHistoryPayload', [new FamilyMemberHistory([
        'status' => 'completed',
        'relationship_code' => 'FTH',
        'relationship_display' => 'Father',
        'condition_code' => 'I10',
        'condition_display' => 'Essential hypertension',
    ]), $patient]);

    expect($familyHistory['resourceType'])->toBe('FamilyMemberHistory')
        ->and($familyHistory['patient']['reference'])->toBe('Patient/PTEST')
        ->and($familyHistory['condition'][0]['code']['coding'][0]['system'])->toBe('http://hl7.org/fhir/sid/icd-10');

    $allergy = $invoke('allergyPayload', [new AllergyIntolerance([
        'category' => 'food',
        'code_system' => 'http://snomed.info/sct',
        'code' => 'SNOMED-TEST',
        'display' => 'Test allergen',
        'clinical_status' => 'active',
        'verification_status' => 'confirmed',
    ]), $patient]);

    expect($allergy['resourceType'])->toBe('AllergyIntolerance')
        ->and($allergy['patient']['reference'])->toBe('Patient/PTEST')
        ->and($allergy['code']['coding'][0]['system'])->toBe('http://snomed.info/sct');

    $medication = $invoke('medicationStatementPayload', [new MedicationStatement([
        'medication_system' => 'http://sys-ids.kemkes.go.id/kfa',
        'medication_code' => 'KFA-TEST',
        'medication_display' => 'Test medication',
        'status' => 'active',
        'dosage' => 'One dose daily',
    ]), $patient]);

    expect($medication['resourceType'])->toBe('MedicationStatement')
        ->and($medication['subject']['reference'])->toBe('Patient/PTEST')
        ->and($medication['medicationCodeableConcept']['coding'][0]['system'])->toBe('http://sys-ids.kemkes.go.id/kfa');
});

it('maps appointment clinical modules to resources linked to patient and encounter', function () {
    $service = app(SatusehatClinicalDataSync::class);
    $patient = new Patient();
    $patient->satusehat_patient_id = 'PTEST';
    $appointment = new Appointment();
    $appointment->setRawAttributes([
        'schedule_date' => '2026-10-02',
        'symptoms' => 'Patient reports headache',
        'objective' => 'Alert and oriented',
        'diagnostic' => 'Primary headache',
        'plan' => 'Follow up in one week',
    ]);
    $appointment->satusehat_encounter_id = 'encounter-test';
    $appointment->satusehat_condition_id = 'condition-test';
    $doctor = new User();
    $doctor->name = 'Test Doctor';
    $doctor->satusehat_practitioner_id = 'NTEST';

    $invoke = function (string $method, array $arguments) use ($service): array {
        $reflection = new \ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($service, $arguments);
    };

    $condition = $invoke('anamnesisConditionPayload', [new AnamnesisCondition([
        'clinical_status' => 'active',
        'verification_status' => 'confirmed',
        'code' => 'I10',
        'display' => 'Essential hypertension',
    ]), $appointment, $patient]);

    expect($condition['resourceType'])->toBe('Condition')
        ->and($condition['subject']['reference'])->toBe('Patient/PTEST')
        ->and($condition['encounter']['reference'])->toBe('Encounter/encounter-test');

    $observation = $invoke('observationPayload', [new SatusehatObservation([
        'category' => 'functional',
        'code_system' => 'http://loinc.org',
        'code' => 'LOINC-TEST',
        'display' => 'Functional test',
        'value_type' => 'quantity',
        'value_quantity' => 1,
        'unit' => 'count',
        'unit_code' => '{count}',
    ]), $appointment, $patient, $doctor]);

    expect($observation['resourceType'])->toBe('Observation')
        ->and($observation['category'][0]['coding'][0]['code'])->toBe('survey')
        ->and($observation['encounter']['reference'])->toBe('Encounter/encounter-test')
        ->and($observation['valueQuantity']['system'])->toBe('http://unitsofmeasure.org');

    $impression = $invoke('clinicalImpressionPayload', [new ClinicalImpression([
        'status' => 'completed',
        'investigation_system' => 'http://snomed.info/sct',
        'investigation_code' => 'INV-TEST',
        'investigation_display' => 'Clinical finding',
        'summary' => 'Test summary',
        'prognosis_system' => 'http://terminology.kemkes.go.id/CodeSystem/clinical-term',
        'prognosis_code' => 'PR-TEST',
        'prognosis_display' => 'Good prognosis',
    ]), $appointment, $patient, $doctor]);

    expect($impression['resourceType'])->toBe('ClinicalImpression')
        ->and($impression['encounter']['reference'])->toBe('Encounter/encounter-test')
        ->and($impression['prognosisCodeableConcept'][0]['coding'][0]['code'])->toBe('PR-TEST')
        ->and($impression['note'][0]['text'])->toContain('S: Patient reports headache')
        ->and($impression['note'][0]['text'])->toContain('O: Alert and oriented')
        ->and($impression['note'][0]['text'])->toContain('A: Primary headache')
        ->and($impression['note'][0]['text'])->toContain('P: Follow up in one week');

    $goal = $invoke('goalPayload', [new CareGoal([
        'lifecycle_status' => 'active',
        'description_system' => 'http://snomed.info/sct',
        'description_code' => 'GOAL-TEST',
        'description_display' => 'Reach target',
    ]), $appointment, $patient]);

    expect($goal['resourceType'])->toBe('Goal')
        ->and($goal['subject']['reference'])->toBe('Patient/PTEST')
        ->and($goal['description']['coding'][0]['code'])->toBe('GOAL-TEST');

    $careGoal = new CareGoal(['description_code' => 'GOAL-TEST']);
    $careGoal->satusehat_id = 'goal-test';
    $appointment->setRelation('careGoals', collect([$careGoal]));
    $carePlan = $invoke('carePlanPayload', [new CarePlan([
        'status' => 'active',
        'intent' => 'plan',
        'title' => 'Test plan',
        'notes' => 'Care plan note',
        'activity_description' => 'Follow-up instruction',
        'activity_status' => 'not-started',
    ]), $appointment, $patient, $doctor]);

    expect($carePlan['resourceType'])->toBe('CarePlan')
        ->and($carePlan['subject']['reference'])->toBe('Patient/PTEST')
        ->and($carePlan['encounter']['reference'])->toBe('Encounter/encounter-test')
        ->and($carePlan['goal'][0]['reference'])->toBe('Goal/goal-test')
        ->and($carePlan['addresses'][0]['reference'])->toBe('Condition/condition-test')
        ->and($carePlan['note'][0]['text'])->toContain('Follow up in one week')
        ->and($carePlan['note'][0]['text'])->toContain('Care plan note');
});
