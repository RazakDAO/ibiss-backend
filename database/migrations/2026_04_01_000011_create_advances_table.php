<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->text('motif')->nullable();
            $table->date('date_demande');
            $table->date('date_remboursement_prevue')->nullable();
            $table->unsignedInteger('montant_rembourse')->default(0);
            $table->enum('statut', ['en_attente', 'approuve', 'rembourse', 'rejete'])->default('en_attente');
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
