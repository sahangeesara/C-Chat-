<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'call_type')) {
                $table->string('call_type', 10)->default('audio')->after('status');
                $table->index(['callee_id', 'call_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (Schema::hasColumn('calls', 'call_type')) {
                $table->dropIndex('calls_callee_id_call_type_index');
                $table->dropColumn('call_type');
            }
        });
    }
};

