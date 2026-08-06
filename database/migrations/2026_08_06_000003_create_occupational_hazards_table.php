<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * occupational_hazards — einzelne Gefährdung einer Beurteilung.
 *
 * Verweist OPTIONAL per morphMap auf einen Katalog-Eintrag (catalog_type/catalog_id) —
 * die „empfohlene Vorsorge" (z. B. arbmedvv_occasion), statt eines harten leistung_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupational_hazards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->foreignId('risk_assessment_id')
                ->constrained('occupational_risk_assessments')->cascadeOnDelete();

            $table->string('category', 40)->default('other'); // HazardCategory
            $table->text('description')->nullable();
            $table->string('risk', 16)->nullable();           // HazardRisk
            $table->text('measures')->nullable();             // Maßnahmen
            $table->string('responsible')->nullable();        // Verantwortlich
            $table->date('deadline')->nullable();             // Frist
            $table->string('status', 24)->default('open');    // HazardStatus
            $table->date('effectiveness_checked_at')->nullable();
            $table->boolean('effective')->nullable();

            // Empfohlene Vorsorge per Katalog-Bezug (morphMap)
            $table->string('catalog_type')->nullable();
            $table->unsignedBigInteger('catalog_id')->nullable();

            $table->timestamps();

            $table->index(['catalog_type', 'catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_hazards');
    }
};
