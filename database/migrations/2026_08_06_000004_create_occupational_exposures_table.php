<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * occupational_exposures — Ausprägung/Gefährdungsfaktor an einer erbrachten Leistung,
 * mit EIGENEM Recall (next_due). service_id ist eine LOSE Referenz auf encounter_services.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupational_exposures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('service_id')->index(); // lose → encounter_services
            $table->foreignId('hazard_id')->nullable()
                ->constrained('occupational_hazards')->nullOnDelete();

            $table->string('label');                 // Bezeichnung des Faktors
            $table->string('type')->nullable();
            $table->string('participation', 24)->nullable();
            $table->date('next_due')->nullable()->index(); // eigener Recall

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_exposures');
    }
};
