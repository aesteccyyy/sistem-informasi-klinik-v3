<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('clinical_status')->default('active');
            $table->string('verification_status')->default('confirmed');
            $table->string('code');
            $table->string('display');
            $table->date('onset_date')->nullable();
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
        Schema::dropIfExists('anamnesis_conditions');
    }
};