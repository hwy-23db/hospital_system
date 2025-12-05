<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the admissions table for tracking each hospital stay.
     * One patient can have multiple admissions over time.
     */
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();

            // Patient reference
            $table->unsignedBigInteger('patient_id');

            // Assigned staff for this admission
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();

            // Admission number (unique per patient, e.g., "ADM-2024-001")
            $table->string('admission_number')->unique();

            // Admission details
            $table->date('admission_date');
            $table->time('admission_time')->nullable();
            $table->text('present_address')->nullable(); // Address at time of admission
            $table->string('admitted_for'); // Chief complaint / reason for admission
            $table->string('referred_by')->nullable();
            $table->string('police_case')->nullable();
            $table->string('service')->nullable(); // Department (Cardiology, Surgery, etc.)
            $table->string('ward')->nullable();
            $table->string('bed_number')->nullable();
            $table->string('medical_officer')->nullable();

            // Initial assessment
            $table->text('initial_diagnosis')->nullable();
            $table->text('drug_allergy_noted')->nullable(); // Allergies noted at admission
            $table->text('remarks')->nullable();

            // Discharge information
            $table->date('discharge_date')->nullable();
            $table->time('discharge_time')->nullable();
            $table->text('discharge_diagnosis')->nullable();
            $table->text('other_diagnosis')->nullable();
            $table->text('external_cause_of_injury')->nullable();
            $table->text('clinician_summary')->nullable();
            $table->text('surgical_procedure')->nullable();
            $table->string('discharge_type')->nullable(); // normal, against_advice, absconded, transferred
            $table->string('discharge_status')->nullable(); // improved, unchanged, worse, dead
            $table->text('discharge_instructions')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();

            // Death information (if applicable)
            $table->string('cause_of_death')->nullable();
            $table->string('autopsy')->nullable();
            $table->datetime('time_of_death')->nullable();

            // Certification
            $table->string('certified_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('attending_doctor_name')->nullable();
            $table->string('attending_doctor_signature')->nullable();

            // Admission status
            $table->enum('status', ['admitted', 'discharged', 'deceased', 'transferred'])->default('admitted');

            // Billing status - DISABLED (Free hospital - no billing required)
            // $table->enum('billing_status', ['pending', 'partial', 'paid', 'waived'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('nurse_id')->references('id')->on('users')->onDelete('set null');

            // Indexes for common queries
            $table->index('status');
            $table->index('admission_date');
            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
