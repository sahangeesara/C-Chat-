<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment');
            }

            if (!Schema::hasColumn('messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_mime');
            }

            if (!Schema::hasColumn('messages', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
            }
        });

        // Ensure text-only messages can still be inserted when attachment is omitted.
        DB::statement("ALTER TABLE `messages` MODIFY `attachment` VARCHAR(2048) NULL DEFAULT ''");
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            foreach (['attachment_mime', 'attachment_name', 'attachment_size'] as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE `messages` MODIFY `attachment` VARCHAR(255) NOT NULL");
    }
};
