<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('symptoms')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing appointments may not have an EMR yet, so symptoms cannot safely be made required again.
    }
};
