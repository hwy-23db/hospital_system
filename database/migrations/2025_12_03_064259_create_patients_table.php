<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the patients table with permanent demographic data only.
     * Admission-specific data is stored in the admissions table.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            
            // Basic patient identification
            $table->string('name');
            $table->string('nrc_number')->nullable()->unique(); // National Registration Card
            $table->string('sex')->nullable();
            $table->integer('age')->nullable();
            $table->date('dob')->nullable(); // Date of birth
            $table->string('contact_phone')->nullable();
            
            // Permanent address
            $table->text('permanent_address')->nullable();
            
            // Personal details (permanent)
            $table->string('marital_status')->nullable();
            $table->string('ethnic_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('occupation')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            
            // Emergency contact
            $table->string('nearest_relative_name')->nullable();
            $table->string('nearest_relative_phone')->nullable();
            $table->string('relationship')->nullable();
            
            // Medical info (permanent)
            $table->string('blood_type')->nullable();
            $table->text('known_allergies')->nullable(); // Drug allergies, food allergies, etc.
            $table->text('chronic_conditions')->nullable(); // Diabetes, hypertension, etc.
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
