<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('semaine_debut'); // lundi de la semaine
            $table->json('horaires'); // {lundi: {debut:'08:00', fin:'17:00'}, ...}
            $table->timestamps();

            $table->unique(['employee_id', 'semaine_debut']);
            $table->index(['company_id', 'semaine_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
