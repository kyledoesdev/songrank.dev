<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /* Projection of the licences table beside is_pro, kept in sync by User::syncProStatus(). */
            $table->timestamp('pro_since')->nullable()->after('is_pro');
        });

        DB::table('users')
            ->where('is_pro', true)
            ->update([
                'pro_since' => DB::table('pro_licenses')
                    ->selectRaw('min(coalesce(purchased_at, created_at))')
                    ->whereColumn('pro_licenses.user_id', 'users.id')
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pro_since');
        });
    }
};
