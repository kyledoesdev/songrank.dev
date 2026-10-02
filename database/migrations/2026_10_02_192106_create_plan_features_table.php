<?php

use Database\Seeders\PlanFeatureSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('plan')->index();
            $table->string('label');
            $table->string('detail')->nullable();
            $table->boolean('is_included')->default(true);
            $table->boolean('is_coming_soon')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        (new PlanFeatureSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
