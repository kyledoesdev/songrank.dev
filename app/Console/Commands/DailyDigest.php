<?php

namespace App\Console\Commands;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kyledoesdev\Essentials\Stats\LoginStat;

class DailyDigest extends Command
{
    protected $signature = 'daily-digest:send';

    protected $description = 'Send a discord message with Daily Stats';

    private Carbon $start;

    private Carbon $end;

    public function __construct()
    {
        parent::__construct();

        $this->start = now()->subDays(1)->startOfDay();
        $this->end = now()->subDays(1)->endOfDay();
    }

    public function handle()
    {
        $message = "Daily Digest {$this->start->format('m/d/Y h:i:s A T')} - {$this->end->format('m/d/Y h:i:s A T')}. \n";

        $message .= "New Users: {$this->getNewUsers()}. \n";
        $message .= "Logins Today: {$this->getLogins()}. \n";
        $message .= "Rankings Started: {$this->getNewRankings()}. \n";
        $message .= "Rankings Deleted: {$this->getDeletedRankings()}. \n";
        $message .= "Rankings Completed: {$this->getCompletedRankings()}. \n";
        $message .= "Tier Lists Started: {$this->getNewTierlists()}. \n";
        $message .= "Tier Lists Deleted: {$this->getDeletedTierlists()}. \n";
        $message .= "Tier Lists Completed: {$this->getCompletedTierlists()}. \n";
        $message .= "Reviews Started: {$this->getNewReviews()}. \n";
        $message .= "Reviews Deleted: {$this->getDeletedReviews()}. \n";
        $message .= "Reviews Completed: {$this->getCompletedReviews()}. \n";

        Log::channel('discord_other_updates')->info($message);

        return Command::SUCCESS;
    }

    private function getNewUsers(): int
    {
        return User::query()
            ->whereBetween('created_at', [$this->start, $this->end])
            ->count();
    }

    private function getLogins(): int
    {
        return LoginStat::query()
            ->start($this->start)
            ->end($this->end)
            ->get()
            ->sum('increments');
    }

    private function getNewRankings(): int
    {
        return Ranking::query()
            ->whereBetween('created_at', [$this->start, $this->end])
            ->count();
    }

    private function getDeletedRankings(): int
    {
        return Ranking::query()
            ->onlyTrashed()
            ->whereBetween('deleted_at', [$this->start, $this->end])
            ->count();
    }

    private function getCompletedRankings(): int
    {
        return Ranking::query()
            ->whereBetween('completed_at', [$this->start, $this->end])
            ->count();
    }

    private function getNewTierlists(): int
    {
        return Tierlist::query()
            ->whereBetween('created_at', [$this->start, $this->end])
            ->count();
    }

    private function getDeletedTierlists(): int
    {
        return Tierlist::query()
            ->onlyTrashed()
            ->whereBetween('deleted_at', [$this->start, $this->end])
            ->count();
    }

    private function getCompletedTierlists(): int
    {
        return Tierlist::query()
            ->whereBetween('completed_at', [$this->start, $this->end])
            ->count();
    }

    private function getNewReviews(): int
    {
        return Review::query()
            ->whereBetween('created_at', [$this->start, $this->end])
            ->count();
    }

    private function getDeletedReviews(): int
    {
        return Review::query()
            ->onlyTrashed()
            ->whereBetween('deleted_at', [$this->start, $this->end])
            ->count();
    }

    private function getCompletedReviews(): int
    {
        return Review::query()
            ->whereBetween('published_at', [$this->start, $this->end])
            ->count();
    }
}
