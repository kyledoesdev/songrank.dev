<?php

namespace App\Livewire\SongRank;

use App\Actions\CompleteSongRankProcess;
use App\Models\Ranking;
use App\Models\RankingSortingState;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SongRankProcess extends Component
{
    public const MAX_UNDO_HISTORY = 10;

    public Ranking $ranking;

    public RankingSortingState $sortingState;

    public array $currentSong1 = [];

    public array $currentSong2 = [];

    public bool $showEmbeds = true;

    public bool $isProcessing = false;

    public bool $canUndo = false;

    public int $progressPercentage = 0;

    protected $listeners = ['toggleEmbeds'];

    public function mount(): void
    {
        $this->ranking->loadMissing('songs', 'songs.artist');
        $this->showEmbeds = ! $this->ranking->isShowType();

        if (! $this->ranking->is_ranked) {
            $this->initializeSorting();
        }
    }

    protected function initializeSorting(): void
    {
        if (! $this->sortingState->sorting_state) {
            $this->startNewSorting();
        }

        $this->canUndo = filled($this->sortingState->sorting_state['decision_history'] ?? []);
        $this->updateProgressBar();
        $this->continueSort();
    }

    protected function startNewSorting(): void
    {
        $songIds = $this->ranking->songs()
            ->inRandomOrder()
            ->pluck('id')
            ->toArray();

        $this->sortingState->update(RankingSortingState::startingWith($songIds));
    }

    protected function continueSort(): void
    {
        $this->sortingState->refresh();
        $state = $this->sortingState->sorting_state;

        if ($state['current_merge']) {
            $this->processMerge();

            return;
        }

        while ($task = array_pop($state['stack'])) {
            if ($task['type'] === 'sort') {
                $this->handleSortTask($task, $state);
            }

            if ($task['type'] === 'merge') {
                $state['current_merge'] = $task;
                $this->sortingState->update(['sorting_state' => $state]);
                $this->processMerge();

                return;
            }
        }

        if (empty($state['stack']) && ! $state['current_merge']) {
            $finalKey = '0-'.(count($this->ranking->songs) - 1);

            if (isset($state['completed_segments'][$finalKey])) {
                $this->complete($state['completed_segments'][$finalKey]);
            }
        }
    }

    protected function handleSortTask(array $task, array &$state): void
    {
        ['start' => $start, 'end' => $end, 'ids' => $ids, 'depth' => $depth] = $task;

        if ($start >= $end) {
            $state['completed_segments']["{$start}-{$end}"] = array_slice($ids, $start, max(1, $end - $start + 1));
            $this->sortingState->update(['sorting_state' => $state]);

            return;
        }

        $mid = intval(($start + $end) / 2);

        /* Push tasks in reverse order (LIFO) */
        array_push($state['stack'],
            [
                'type' => 'merge',
                'ids' => $ids,
                'start' => $start,
                'mid' => $mid,
                'end' => $end,
                'left_key' => "{$start}-{$mid}",
                'right_key' => ($mid + 1)."-{$end}",
                'depth' => $depth + 1,
            ],
            [
                'type' => 'sort',
                'ids' => $ids,
                'start' => $mid + 1,
                'end' => $end,
                'depth' => $depth + 1,
            ],
            [
                'type' => 'sort',
                'ids' => $ids,
                'start' => $start,
                'end' => $mid,
                'depth' => $depth + 1,
            ]
        );

        $this->sortingState->update(['sorting_state' => $state]);
    }

    protected function processMerge(): void
    {
        $state = $this->sortingState->sorting_state;
        $merge = $state['current_merge'] ?? null;

        if (is_null($merge)) {
            $this->continueSort();

            return;
        }

        if (! isset($merge['left_ids'])) {
            $merge = array_merge($merge, [
                'left_ids' => array_values($state['completed_segments'][$merge['left_key']] ?? []),
                'right_ids' => array_values($state['completed_segments'][$merge['right_key']] ?? []),
                'result_ids' => [],
            ]);

            $state['current_merge'] = $merge;
            $this->sortingState->update(['sorting_state' => $state]);
        }

        if (filled($merge['left_ids']) && filled($merge['right_ids'])) {
            $songs = $this->ranking->songs
                ->whereIn('id', [$merge['left_ids'][0], $merge['right_ids'][0]])
                ->keyBy('id');

            $this->currentSong1 = [
                ...$songs[$merge['left_ids'][0]]->only(['id', 'title', 'cover', 'spotify_song_id']),
                'is_podcast' => $songs[$merge['left_ids'][0]]->artist->is_podcast,
            ];

            $this->currentSong2 = [
                ...$songs[$merge['right_ids'][0]]->only(['id', 'title', 'cover', 'spotify_song_id']),
                'is_podcast' => $songs[$merge['right_ids'][0]]->artist->is_podcast,
            ];
        } else {
            $result = [...$merge['result_ids'], ...$merge['left_ids'], ...$merge['right_ids']];
            $key = "{$merge['start']}-{$merge['end']}";

            $state['completed_segments'][$key] = $result;
            $state['current_merge'] = null;

            $this->sortingState->update(['sorting_state' => $state]);
            $this->continueSort();
        }
    }

    public function chooseSong(int $songId): void
    {
        if ($this->isProcessing) {
            return;
        }

        $this->isProcessing = true;

        try {
            $state = $this->sortingState->sorting_state;
            $merge = $state['current_merge'] ?? null;

            if (is_null($merge)) {
                return;
            }

            if (! in_array($songId, [$this->currentSong1['id'], $this->currentSong2['id']])) {
                return;
            }

            $decisionHistory = $state['decision_history'] ?? [];
            $decisionHistory[] = collect($state)
                ->except('decision_history')
                ->put('completed_comparisons', $this->sortingState->completed_comparisons)
                ->all();

            if (count($decisionHistory) > self::MAX_UNDO_HISTORY) {
                array_shift($decisionHistory);
            }

            $chosenFromLeft = $songId === $this->currentSong1['id'];

            $merge['result_ids'][] = $chosenFromLeft
                ? array_shift($merge['left_ids'])
                : array_shift($merge['right_ids']);

            $state['current_merge'] = $merge;
            $state['decision_history'] = $decisionHistory;

            DB::transaction(function () use ($state) {
                $this->sortingState->increment('completed_comparisons');
                $this->sortingState->update(['sorting_state' => $state]);
            });

            $this->canUndo = true;
            $this->updateProgressBar();
            $this->processMerge();

        } catch (Exception $e) {
            Log::channel('discord_other_updates')->error('Error in chooseSong', ['error' => $e->getMessage()]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function undoLastChoice(): void
    {
        $state = $this->sortingState->sorting_state;
        $decisionHistory = $state['decision_history'] ?? [];

        if ($this->isProcessing || empty($decisionHistory)) {
            return;
        }

        $this->isProcessing = true;

        try {
            $snapshot = array_pop($decisionHistory);
            $completedComparisons = $snapshot['completed_comparisons'];
            unset($snapshot['completed_comparisons']);

            $snapshot['decision_history'] = $decisionHistory;

            DB::transaction(function () use ($snapshot, $completedComparisons) {
                $this->sortingState->update([
                    'sorting_state' => $snapshot,
                    'completed_comparisons' => $completedComparisons,
                ]);
            });

            $this->canUndo = filled($decisionHistory);
            $this->updateProgressBar();
            $this->processMerge();
        } catch (Exception $e) {
            Log::channel('discord_other_updates')->error('Error in undoLastChoice', ['error' => $e->getMessage()]);
        } finally {
            $this->isProcessing = false;
        }
    }

    protected function updateProgressBar(): void
    {
        $total = $this->sortingState->aprox_comparisons;
        $completed = $this->sortingState->completed_comparisons;

        $this->progressPercentage = $total > 0
            ? min(100, intval(($completed / $total) * 100))
            : 0;
    }

    protected function complete(array $finalSongIds): void
    {
        $this->js('window.showLoader()');

        (new CompleteSongRankProcess)->handle($this->ranking, [
            'finalSongIds' => $finalSongIds,
        ]);

        $this->redirect(route('ranking', ['id' => $this->ranking->getKey()]), navigate: true);
    }

    public function toggleEmbeds(): void
    {
        $this->showEmbeds = ! $this->showEmbeds;
    }

    public function render()
    {
        return view('livewire.song-rank.song-rank-process');
    }
}
