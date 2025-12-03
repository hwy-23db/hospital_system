<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nrc_number');
            $table->string('sex')->nullable();
            $table->integer('age')->nullable();
            $table->date('dob')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('ethnic_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('occupation')->nullable();
            $table->date('prev_admission_date')->nullable();
            $table->string('nearest_relative_name')->nullable();
            $table->string('relationship')->nullable();
            $table->string('referred_by')->nullable();
            $table->string('police_case')->nullable();
            $table->text('present_address')->nullable();
            $table->string('medical_officer')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('service')->nullable();
            $table->string('ward')->nullable();
            $table->string('father_name')->nullable();
            $table->date('admission_date')->nullable();
            $table->time('admission_time')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('discharge_date')->nullable();
            $table->time('discharge_time')->nullable();
            $table->string('admitted_for')->nullable();
            $table->string('drug_allergy')->nullable();
            $table->text('remarks')->nullable();
            $table->text('discharge_diagnosis')->nullable();
            $table->text('other_diagnosis')->nullable();
            $table->text('external_cause_of_injury')->nullable();
            $table->text('clinician_summary')->nullable();
            $table->text('surgical_procedure')->nullable();
            $table->string('discharge_type')->nullable();
            $table->string('discharge_status')->nullable();
            $table->string('treatment_record')->nullable();
            $table->string('cause_of_death')->nullable();
            $table->string('autopsy')->nullable();
            $table->string('certified_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_signature')->nullable();
            $table->timestamps();
            // $table->unsignedBigInteger('doctor_id')->nullable();
            // $table->unsignedBigInteger('nurse_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
