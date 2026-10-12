<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_user', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->unsignedBigInteger('clinic_id')->nullable()->change();
            $table->foreign('clinic_id')
                ->references('id')
                ->on('clinics')
                ->nullOnDelete();
        });

        Schema::table('drugs', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->unsignedBigInteger('clinic_id')->nullable()->change();
            $table->foreign('clinic_id')
                ->references('id')
                ->on('clinics')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->unsignedBigInteger('clinic_id')->nullable(false)->change();
            $table->foreign('clinic_id')->references('id')->on('clinics');
        });

        Schema::table('clinic_user', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->unsignedBigInteger('clinic_id')->nullable(false)->change();
            $table->foreign('clinic_id')->references('id')->on('clinics');
        });
    }
};