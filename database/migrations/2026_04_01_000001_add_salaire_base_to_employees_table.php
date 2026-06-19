<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('salaire_base')->default(0)->after('department');
            $table->string('cnss_numero', 50)->nullable()->after('salaire_base');
            $table->string('iuts_numero', 50)->nullable()->after('cnss_numero');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['salaire_base', 'cnss_numero', 'iuts_numero']);
        });
    }
};
