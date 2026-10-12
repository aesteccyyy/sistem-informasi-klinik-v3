<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->foreign('appointment_id')
                ->references('id')
                ->on('appointments')
                ->cascadeOnDelete();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->foreign('appointment_id')
                ->references('id')
                ->on('appointments')
                ->cascadeOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
            $table->foreign('appointment_id')
                ->references('id')
                ->on('appointments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable(false)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });
    }
};