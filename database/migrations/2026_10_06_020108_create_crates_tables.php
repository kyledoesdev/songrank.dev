<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('crates')->cascadeOnDelete();
            $table->foreignId('crate_icon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 40);
            $table->string('color', 7)->default('#A855F7');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'parent_id', 'position']);
        });

        Schema::create('crate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crate_id')->constrained()->cascadeOnDelete();
            $table->morphs('entity');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['crate_id', 'position']);
            $table->unique(['crate_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crate_items');
        Schema::dropIfExists('crates');
    }
};
