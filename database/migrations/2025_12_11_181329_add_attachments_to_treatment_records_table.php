<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add attachments field to store multiple PDF file uploads for treatment records.
     */
    public function up(): void
    {
        Schema::table('treatment_records', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('complications')
                ->comment('JSON array of uploaded PDF file paths/URLs for medical documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_records', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
