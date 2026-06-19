<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tuteur_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('nom_complet');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('ecole')->nullable();
            $table->enum('type', ['stage', 'apprentissage'])->default('stage');
            $table->string('poste');
            $table->string('departement')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedInteger('gratification')->default(0); // en F CFA
            $table->enum('statut', ['en_cours', 'termine', 'annule'])->default('en_cours');
            $table->text('objectifs')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
