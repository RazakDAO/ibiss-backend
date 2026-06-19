<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruiter_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('sector')->index();
            $table->string('city')->index();
            $table->enum('type', ['cdi', 'cdd', 'stage', 'freelance', 'partiel'])->index();
            $table->enum('level', ['sans_experience', 'junior', 'confirme', 'senior'])->index();
            $table->string('salary_range')->nullable();
            $table->enum('status', ['draft', 'pending', 'active', 'expired', 'archived'])->default('draft')->index();
            $table->boolean('is_sponsored')->default(false)->index();
            $table->timestamp('sponsored_until')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
