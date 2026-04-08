<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'group_id')) {
                $table->foreignId('group_id')->nullable()->after('callee_id')->constrained('chat_groups')->nullOnDelete();
            }

            if (!Schema::hasColumn('calls', 'participants')) {
                $table->json('participants')->nullable()->after('answer_sdp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (Schema::hasColumn('calls', 'group_id')) {
                $table->dropConstrainedForeignId('group_id');
            }

            if (Schema::hasColumn('calls', 'participants')) {
                $table->dropColumn('participants');
            }
        });
    }
};

