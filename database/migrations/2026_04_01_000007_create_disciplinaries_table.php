<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('type', ['avertissement_oral', 'avertissement_ecrit', 'mise_a_pied', 'suspension', 'licenciement']);
            $table->enum('gravite', ['faible', 'moyen', 'grave'])->default('faible');
            $table->date('date_sanction');
            $table->text('motif');
            $table->text('consequences')->nullable();
            $table->enum('statut', ['actif', 'archive'])->default('actif');
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinaries');
    }
};
