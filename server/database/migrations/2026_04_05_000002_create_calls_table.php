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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('callee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('ringing');
            $table->json('offer')->nullable();
            $table->json('answer_sdp')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['caller_id', 'callee_id']);
            $table->index(['callee_id', 'status']);
            $table->index(['caller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};

