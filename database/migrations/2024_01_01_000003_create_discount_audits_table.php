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
        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->enum('action', ['assigned', 'revoked', 'applied', 'failed']);
            $table->decimal('original_amount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2)->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('performed_by')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at');

            // Indexes for audit queries
            $table->index(['user_id', 'created_at']);
            $table->index(['discount_id', 'action']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_audits');
    }
};
