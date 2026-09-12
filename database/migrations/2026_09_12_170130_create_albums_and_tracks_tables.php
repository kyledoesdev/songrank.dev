<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('album_id')->unique();
            $table->foreignId('artist_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->string('name');
            $table->text('cover')->nullable();
            $table->string('release_date')->nullable();
            $table->unsignedInteger('total_tracks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('track_id')->unique();
            $table->foreignId('artist_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->string('name');
            $table->text('cover')->nullable();
            $table->string('album_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
        Schema::dropIfExists('albums');
    }
};
