<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique();
            $table->string('satusehat_patient_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->string('satusehat_organization_id')->nullable();
            $table->string('satusehat_location_id')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('satusehat_practitioner_id')->nullable();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('satusehat_encounter_id')->nullable()->index();
            $table->string('satusehat_sync_status')->default('pending');
            $table->text('satusehat_sync_error')->nullable();
            $table->timestamp('satusehat_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'satusehat_encounter_id',
                'satusehat_sync_status',
                'satusehat_sync_error',
                'satusehat_synced_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('satusehat_practitioner_id');
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['satusehat_organization_id', 'satusehat_location_id']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropIndex(['satusehat_patient_id']);
            $table->dropColumn([
                'nik',
                'satusehat_patient_id',
                'satusehat_sync_status',
                'satusehat_sync_error',
                'satusehat_synced_at',
            ]);
        });
    }
};