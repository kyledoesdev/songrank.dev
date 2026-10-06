<?php

use Database\Seeders\CrateIconSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crate_icons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->unique();
            $table->boolean('is_pro')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        (new CrateIconSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('crate_icons');
    }
};
