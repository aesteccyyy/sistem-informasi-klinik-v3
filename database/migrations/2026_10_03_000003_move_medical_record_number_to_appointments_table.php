<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('visit_number', 4)->nullable()->unique();
        });

        DB::table('appointments')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($appointments): void {
                foreach ($appointments as $appointment) {
                    DB::table('appointments')
                        ->where('id', $appointment->id)
                        ->update(['visit_number' => str_pad((string) $appointment->id, 4, '0', STR_PAD_LEFT)]);
                }
            });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('visit_number', 4)->nullable(false)->change();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['medical_record_number']);
            $table->dropColumn('medical_record_number');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('medical_record_number', 20)->nullable()->unique();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['visit_number']);
            $table->dropColumn('visit_number');
        });
    }
};
