<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds admission_type to track outpatient vs inpatient.
     */
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // Add admission type after patient_id
            $table->enum('admission_type', ['outpatient', 'inpatient'])
                ->default('inpatient')
                ->after('patient_id');

            // Add index for common queries
            $table->index('admission_type');
            $table->index(['patient_id', 'admission_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['patient_id', 'admission_type']);
            $table->dropIndex(['admission_type']);
            $table->dropColumn('admission_type');
        });
    }
};
