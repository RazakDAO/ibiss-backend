<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('work_location')->nullable();
            $table->enum('contract_type', ['cdi', 'cdd', 'stage', 'freelance', 'partiel'])->default('cdi');
            $table->enum('contract_status', ['en_cours', 'termine', 'en_attente', 'suspendu'])->default('en_cours');
            $table->enum('access_status', ['actif', 'inactif', 'invite'])->default('actif');
            $table->date('hired_at');
            $table->date('end_date')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('meta')->nullable(); // données supplémentaires
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'contract_status']);
            $table->index('hired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
