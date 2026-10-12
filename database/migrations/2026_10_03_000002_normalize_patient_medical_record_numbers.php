<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['medical_record_number']);
        });

        DB::table('patients')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($patients): void {
                foreach ($patients as $patient) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update(['medical_record_number' => str_pad((string) $patient->id, 4, '0', STR_PAD_LEFT)]);
                }
            });

        Schema::table('patients', function (Blueprint $table) {
            $table->unique('medical_record_number');
        });
    }

    public function down(): void
    {
        // The previous format is intentionally not restored.
    }
};
