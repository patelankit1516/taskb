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
        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->string('assigned_by')->nullable(); // Admin/System user who assigned
            $table->string('revoked_by')->nullable(); // Admin/System user who revoked
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite unique index to prevent duplicate assignments
            $table->unique(['user_id', 'discount_id'], 'user_discount_unique');
            
            // Indexes for queries
            $table->index(['user_id', 'revoked_at']);
            $table->index('discount_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_discounts');
    }
};
