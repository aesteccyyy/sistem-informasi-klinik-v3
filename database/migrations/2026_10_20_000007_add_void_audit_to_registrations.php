<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
        });

        Schema::table('care_encounters', function (Blueprint $table): void {
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('care_encounters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropIndex(['voided_at']);
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropIndex(['voided_at']);
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
