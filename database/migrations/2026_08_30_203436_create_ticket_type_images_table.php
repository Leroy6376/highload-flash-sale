<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_type_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('collection');
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['ticket_type_id', 'collection', 'sort_order']);
        });

        DB::statement("CREATE UNIQUE INDEX ticket_type_images_one_announcement_per_ticket_type ON ticket_type_images (ticket_type_id) WHERE collection = 'announcement'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ticket_type_images_one_announcement_per_ticket_type');

        Schema::dropIfExists('ticket_type_images');
    }
};
