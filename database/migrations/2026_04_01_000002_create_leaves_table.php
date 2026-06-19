<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('approbateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['conge_annuel', 'maladie', 'maternite', 'paternite', 'sans_solde', 'autre'])->default('conge_annuel');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedTinyInteger('nb_jours');
            $table->text('motif')->nullable();
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'statut']);
            $table->index(['employee_id', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
