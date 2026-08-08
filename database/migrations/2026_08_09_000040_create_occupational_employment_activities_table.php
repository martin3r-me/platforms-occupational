<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Person↔Tätigkeit-Bindung (temporal). Eine Beschäftigung hat 1..n Tätigkeiten über die Zeit
 * (multi + Historie in einer Struktur). `activity_id` = lose Referenz auf customer_activities.
 * `valid_until` NULL = aktuell. Person-Soll = Vereinigung der aktuellen Tätigkeiten.
 *
 * Idempotent: erster Lauf scheiterte am zu langen Auto-Index-Namen (>64), Tabelle war da schon
 * angelegt. Compound-Index daher kurz benannt + Anlegen guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabelle existiert bereits (Teil-Lauf) → nur den kurz benannten Compound-Index nachziehen.
        if (Schema::hasTable('occupational_employment_activities')) {
            try {
                Schema::table('occupational_employment_activities', function (Blueprint $table) {
                    $table->index(['employment_id', 'valid_until'], 'oea_emp_valid_idx');
                });
            } catch (\Throwable $e) {
                // Index existiert bereits — ok.
            }
            return;
        }

        Schema::create('occupational_employment_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('employment_id')->index();
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->unsignedBigInteger('activity_id')->index();   // customer_activities (lose)
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();               // NULL = aktuell
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['employment_id', 'valid_until'], 'oea_emp_valid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_employment_activities');
    }
};
