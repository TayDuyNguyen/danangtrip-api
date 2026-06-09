<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tours ALTER COLUMN thumbnail TYPE text');
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE tours
            ALTER COLUMN thumbnail TYPE varchar(255)
            USING left(thumbnail, 255)
        ");
    }
};
