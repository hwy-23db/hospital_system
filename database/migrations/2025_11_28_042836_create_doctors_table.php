<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number');
            // $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('nrc_number');
            $table->string('email')->unique();
            $table->string('specialization');
            $table->timestamps();
        });
    }

};
