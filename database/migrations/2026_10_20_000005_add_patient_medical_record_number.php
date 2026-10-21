<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('medical_record_number', 32)->nullable()->unique();
        });

        DB::table('patients')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($patients): void {
                foreach ($patients as $patient) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update([
                            'medical_record_number' => 'RM-' . str_pad((string) $patient->id, 8, '0', STR_PAD_LEFT),
                        ]);
                }
            });

        Schema::table('patients', function (Blueprint $table): void {
            $table->string('medical_record_number', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropUnique(['medical_record_number']);
            $table->dropColumn('medical_record_number');
        });
    }
};
