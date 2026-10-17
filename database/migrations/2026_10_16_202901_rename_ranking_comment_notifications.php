<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->where('type', 'App\Notifications\NewCommentOnRanking')
            ->update(['type' => 'App\Notifications\NewComment']);
    }

    public function down(): void
    {
        DB::table('notifications')
            ->where('type', 'App\Notifications\NewComment')
            ->update(['type' => 'App\Notifications\NewCommentOnRanking']);
    }
};
