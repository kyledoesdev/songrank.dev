<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->nullableMorphs('subject');
            $table->string('name');
            $table->decimal('stars', 3, 1)->nullable();
            $table->longText('body')->nullable();
            $table->text('body_text')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('comments_enabled')->default(false);
            $table->boolean('comments_replies_enabled')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
