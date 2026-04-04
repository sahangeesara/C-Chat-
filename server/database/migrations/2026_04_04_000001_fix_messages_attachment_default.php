<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make text-only messages safe even when attachment is omitted.
        DB::statement("ALTER TABLE `messages` MODIFY `attachment` VARCHAR(255) NULL DEFAULT ''");
        DB::statement("ALTER TABLE `messages` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `messages` MODIFY `attachment` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `messages` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 0");
    }
};

