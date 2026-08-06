<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * occupational_employments — Beschäftigung: Patient ↔ Firma (betriebsmed. Sonderfall).
 *
 * BEIDE Bezüge sind LOSE: patient_id → patient_records (anderes Modul),
 * company_id → CRM-Company (contract-basiert). Kein DB-FK, damit patient fachneutral
 * bleibt und der Firmen-Bezug ausschließlich hier im Fachmodul lebt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupational_employments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();

            $table->unsignedBigInteger('patient_id')->index(); // lose → patient_records
            $table->unsignedBigInteger('company_id')->nullable()->index(); // lose → CRM-Company

            $table->string('position')->nullable();
            $table->string('personnel_number', 64)->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('first_aider')->default(false); // Ersthelfer
            $table->text('work_notes')->nullable();          // Arbeitshinweise
            $table->text('risk')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_employments');
    }
};
