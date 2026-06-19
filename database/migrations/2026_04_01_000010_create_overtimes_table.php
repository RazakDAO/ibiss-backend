<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('nb_heures', 4, 2);
            $table->text('motif')->nullable();
            $table->unsignedTinyInteger('taux_majoration')->default(125); // 125% = +25%
            $table->unsignedInteger('montant')->default(0);
            $table->enum('statut', ['en_attente', 'approuve', 'paye', 'refuse'])->default('en_attente');
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
