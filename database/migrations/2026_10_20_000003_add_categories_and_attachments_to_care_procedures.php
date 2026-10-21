<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_procedures', function (Blueprint $table): void {
            $table->string('category', 24)->default('procedure')->after('name')->index();
            $table->string('attachment_path')->nullable()->after('performed_at');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('care_procedures', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'attachment_path', 'attachment_name', 'attachment_mime']);
        });
    }
};
