<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('height')->nullable()->change();
            $table->integer('weight')->nullable()->change();
            $table->string('blood_pressure')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Clinical values remain nullable so scheduled appointments can exist before EMR entry.
    }
};
