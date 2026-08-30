<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_amount');
            $table->char('currency', 3)->default('RUB');
            $table->unsignedInteger('capacity');
            $table->unsignedSmallInteger('sales_limit_per_user')->nullable();
            $table->timestampTz('sales_starts_at')->nullable();
            $table->timestampTz('sales_ends_at')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
