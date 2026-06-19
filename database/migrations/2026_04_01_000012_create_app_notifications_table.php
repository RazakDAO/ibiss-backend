<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['candidature', 'offre', 'paiement', 'alerte', 'systeme'])->default('systeme');
            $table->string('titre');
            $table->text('message');
            $table->string('lien')->nullable();
            $table->boolean('lue')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'lue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
