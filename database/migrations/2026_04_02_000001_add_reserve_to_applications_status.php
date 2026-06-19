<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check");
        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ('new','reviewing','interview','selected','reserve','rejected'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check");
        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ('new','reviewing','interview','selected','rejected'))");
    }
};
