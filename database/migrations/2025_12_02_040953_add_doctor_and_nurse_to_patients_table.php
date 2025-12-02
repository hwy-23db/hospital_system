<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_id')->nullable()->after('id');
            $table->unsignedBigInteger('nurse_id')->nullable()->after('doctor_id');

            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('nurse_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropForeign(['nurse_id']);
            $table->dropColumn(['doctor_id', 'nurse_id']);
        });
    }
};
