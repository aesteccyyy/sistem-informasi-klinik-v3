<?php

use App\Enums\AppointmentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('clinic_user_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('queue_number')->nullable()->after('visit_number');
            $table->string('queue_status')->default('waiting')->after('queue_number');
            $table->timestamp('queue_called_at')->nullable()->after('queue_status');
            $table->unique(['clinic_id', 'schedule_date', 'queue_number'], 'appointments_daily_queue_unique');
            $table->index(['clinic_id', 'schedule_date', 'queue_status'], 'appointments_queue_lookup_index');
        });

        Schema::create('appointment_queue_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['clinic_id', 'queue_date'], 'appointment_queue_counter_clinic_date_unique');
        });

        $appointments = DB::table('appointments')
            ->join('clinic_user', 'clinic_user.id', '=', 'appointments.clinic_user_id')
            ->whereNotNull('clinic_user.clinic_id')
            ->orderBy('appointments.schedule_date')
            ->orderBy('clinic_user.clinic_id')
            ->orderBy('appointments.id')
            ->select([
                'appointments.id',
                'appointments.schedule_date',
                'appointments.status',
                'clinic_user.clinic_id',
            ])
            ->get();

        $counters = [];
        foreach ($appointments as $appointment) {
            $key = $appointment->clinic_id . ':' . $appointment->schedule_date;
            $number = ($counters[$key] ?? 0) + 1;
            $counters[$key] = $number;

            DB::table('appointments')->where('id', $appointment->id)->update([
                'clinic_id' => $appointment->clinic_id,
                'queue_number' => $number,
                'queue_status' => $appointment->status === AppointmentStatusEnum::CONFIRMED->value ? 'served' : 'waiting',
            ]);
        }

        foreach ($counters as $key => $lastNumber) {
            [$clinicId, $queueDate] = explode(':', $key, 2);
            DB::table('appointment_queue_counters')->insert([
                'clinic_id' => $clinicId,
                'queue_date' => $queueDate,
                'last_number' => $lastNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_queue_counters');
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique('appointments_daily_queue_unique');
            $table->dropIndex('appointments_queue_lookup_index');
            $table->dropConstrainedForeignId('clinic_id');
            $table->dropColumn(['queue_number', 'queue_status', 'queue_called_at']);
        });
    }
};
