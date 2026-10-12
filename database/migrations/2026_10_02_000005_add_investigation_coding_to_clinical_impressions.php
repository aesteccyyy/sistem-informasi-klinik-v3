<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_impressions', function (Blueprint $table) {
            $table->string('investigation_system')->default('http://snomed.info/sct');
            $table->string('investigation_code')->nullable();
            $table->string('investigation_display')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_impressions', function (Blueprint $table) {
            $table->dropColumn(['investigation_system', 'investigation_code', 'investigation_display']);
        });
    }
};