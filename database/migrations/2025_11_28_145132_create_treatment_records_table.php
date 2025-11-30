<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('treatment_records', function (Blueprint $table) {
        $table->id();

        // Relationships
        $table->unsignedBigInteger('patient_id');
        $table->unsignedBigInteger('doctor_id')->nullable();

        // Treatment Info
        // $table->string('visit_reason')->nullable();
        $table->string('diagnosis')->nullable();
        $table->text('treatment_details')->nullable();
        $table->text('notes')->nullable();
        $table->date('treatment_date')->nullable();
        // $table->date('follow_up_date')->nullable();

        $table->timestamps();

        // Foreign keys
        $table->foreign('patient_id')
              ->references('id')->on('patients')
              ->onDelete('cascade');

        $table->foreign('doctor_id')
              ->references('id')->on('doctors')
              ->onDelete('set null');
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
