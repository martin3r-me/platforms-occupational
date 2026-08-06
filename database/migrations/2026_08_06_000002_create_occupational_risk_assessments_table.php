<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * occupational_risk_assessments — Gefährdungsbeurteilung je Firma/Arbeitsbereich (§5/6 ArbSchG).
 * company_id ist eine LOSE Referenz auf die CRM-Company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupational_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('company_id')->index(); // lose → CRM-Company

            $table->string('title')->nullable();
            $table->string('work_area')->nullable();      // Arbeitsbereich
            $table->date('assessed_on')->nullable();      // Stand-Datum
            $table->date('next_review')->nullable();      // nächste Überprüfung
            $table->string('status', 32)->default('draft'); // AssessmentStatus
            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_risk_assessments');
    }
};
