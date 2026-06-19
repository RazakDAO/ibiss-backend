<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('genere_par')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('mois'); // 1–12
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('salaire_brut');
            $table->unsignedInteger('cnss_patronal');   // 16 %
            $table->unsignedInteger('cnss_salarial');   // 5.5 %
            $table->unsignedInteger('iuts');
            $table->unsignedInteger('heures_supp_montant')->default(0);
            $table->unsignedInteger('avances_deduites')->default(0);
            $table->unsignedInteger('net_a_payer');
            $table->enum('statut', ['brouillon', 'valide', 'paye'])->default('brouillon');
            $table->date('date_paiement')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'mois', 'annee']);
            $table->index(['company_id', 'annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
