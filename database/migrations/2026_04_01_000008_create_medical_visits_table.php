<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date_visite');
            $table->date('date_prochaine_visite')->nullable();
            $table->string('medecin')->nullable();
            $table->string('lieu')->nullable();
            $table->enum('resultat', ['apte', 'inapte', 'apte_reserves'])->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index('date_prochaine_visite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_visits');
    }
};
