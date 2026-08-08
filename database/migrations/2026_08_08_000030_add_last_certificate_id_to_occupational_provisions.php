<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vorsorge-Kartei — Rückbindung an die dokumentierende Bescheinigung.
 * LOSE Referenz auf encounter_certificates (kein DB-FK, wie alle Cross-Modul-Bezüge):
 * „Welche Bescheinigung hat diese Vorsorge zuletzt fortgeschrieben?"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupational_provisions', function (Blueprint $table) {
            $table->unsignedBigInteger('last_certificate_id')->nullable()->after('next_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('occupational_provisions', function (Blueprint $table) {
            $table->dropColumn('last_certificate_id');
        });
    }
};
