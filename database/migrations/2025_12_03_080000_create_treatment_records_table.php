<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the treatment_records table for patient medical history.
     * Links to specific admissions for proper medical history tracking.
     */
    public function up(): void
    {
        Schema::create('treatment_records', function (Blueprint $table) {
            $table->id();

            // Relationships - treatment belongs to an admission
            $table->unsignedBigInteger('admission_id');
            $table->unsignedBigInteger('patient_id'); // Denormalized for easier queries
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();

            // Treatment details
            $table->enum('treatment_type', [
                'surgery',
                'radiotherapy',
                'chemotherapy',
                'targeted_therapy',
                'hormone_therapy',
                'immunotherapy',
                'intervention_therapy',
                'medication',
                'physical_therapy',
                'supportive_care',
                'diagnostic',
                'consultation',
                'procedure',
                'other'
            ]);

            // Treatment information
            $table->string('treatment_name')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('medications')->nullable();
            $table->text('dosage')->nullable();
            $table->date('treatment_date')->nullable();
            $table->time('treatment_time')->nullable();

            // Results/Outcome
            $table->text('results')->nullable();
            $table->text('findings')->nullable();
            $table->enum('outcome', ['pending', 'successful', 'partial', 'unsuccessful', 'ongoing', 'completed'])->default('pending');

            // For procedures/surgeries
            $table->text('pre_procedure_notes')->nullable();
            $table->text('post_procedure_notes')->nullable();
            $table->text('complications')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('admission_id')->references('id')->on('admissions')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('nurse_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('treatment_date');
            $table->index(['admission_id', 'treatment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_records');
    }
};
