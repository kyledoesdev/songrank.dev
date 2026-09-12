<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tierlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->nullableMorphs('source');
            $table->string('name');
            $table->boolean('is_complete')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('comments_enabled')->default(false);
            $table->boolean('comments_replies_enabled')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            /* Drives the per-type allowance check on every setup page load. */
            $table->index(['user_id', 'type']);
        });

        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tierlist_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 7);
            $table->unsignedInteger('position');
            $table->boolean('is_bank')->default(false);
            $table->timestamps();

            $table->unique(['tierlist_id', 'slug']);
            $table->index(['tierlist_id', 'position']);
        });

        Schema::create('tierlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tierlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained()->cascadeOnDelete();
            $table->morphs('entryable');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['tier_id', 'position']);

            /* One entry cannot sit on the same list twice. Items hard delete, so
               no trashed row can block re-adding something you took off the board. */
            $table->unique(['tierlist_id', 'entryable_type', 'entryable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tierlist_items');
        Schema::dropIfExists('tiers');
        Schema::dropIfExists('tierlists');
    }
};
