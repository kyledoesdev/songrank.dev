<?php

namespace App\QueryBuilders\Concerns;

/**
 * For a builder already narrowed to one ordered list, whose positions run
 * from zero with no gaps.
 */
trait HasPositions
{
    /**
     * Closes the gap a row leaves behind at $position.
     */
    public function closeGapAt(int $position): int
    {
        return $this->where('position', '>', $position)->decrement('position');
    }

    /**
     * Makes room for a row moving from $from to $to by shifting only the rows
     * in between. The moving row must already be excluded.
     */
    public function makeRoomFor(int $from, int $to): void
    {
        if ($to < $from) {
            $this->whereBetween('position', [$to, $from - 1])->increment('position');
        }

        if ($to > $from) {
            $this->whereBetween('position', [$from + 1, $to])->decrement('position');
        }
    }
}
