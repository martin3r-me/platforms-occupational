<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Betrieb = Org-Entity, nicht CRM. `company_id` → `organization_entity_id`.
 * LOSE Referenz auf organization_entities (kein DB-FK), wie alle Cross-Modul-Bezüge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupational_employments', function (Blueprint $table) {
            $table->renameColumn('company_id', 'organization_entity_id');
        });
        Schema::table('occupational_risk_assessments', function (Blueprint $table) {
            $table->renameColumn('company_id', 'organization_entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('occupational_employments', function (Blueprint $table) {
            $table->renameColumn('organization_entity_id', 'company_id');
        });
        Schema::table('occupational_risk_assessments', function (Blueprint $table) {
            $table->renameColumn('organization_entity_id', 'company_id');
        });
    }
};
