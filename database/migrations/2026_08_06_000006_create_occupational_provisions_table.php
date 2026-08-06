<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * occupational_provisions — arbeitsmedizinische Vorsorge (ArbMedVV) je Person.
 * patient_id lose → patient. occasion_type/occasion_id per morphMap → Katalog (arbmedvv_occasion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupational_provisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('patient_id')->index(); // lose → patient

            // Katalog-Anlass (morphMap): arbmedvv_occasion
            $table->string('occasion_type')->nullable();
            $table->unsignedBigInteger('occasion_id')->nullable();

            $table->string('type', 24)->default('mandatory'); // CareType
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->date('last_done_at')->nullable();
            $table->date('next_due_at')->nullable()->index();  // Recall/Frist
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->index(['occasion_type', 'occasion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupational_provisions');
    }
};
