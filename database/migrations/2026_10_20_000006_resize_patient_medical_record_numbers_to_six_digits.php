<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->rewriteNumbers(6, '');
    }

    public function down(): void
    {
        $this->rewriteNumbers(8, 'RM-');
    }

    private function rewriteNumbers(int $digits, string $prefix): void
    {
        DB::table('patients')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($patients) use ($digits, $prefix): void {
                foreach ($patients as $patient) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update([
                            'medical_record_number' => $prefix . str_pad((string) $patient->id, $digits, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }
};
