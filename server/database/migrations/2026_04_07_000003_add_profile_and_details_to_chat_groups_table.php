<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_groups', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('chat_groups', 'profile_image_path')) {
                $table->string('profile_image_path')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            if (Schema::hasColumn('chat_groups', 'profile_image_path')) {
                $table->dropColumn('profile_image_path');
            }

            if (Schema::hasColumn('chat_groups', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

