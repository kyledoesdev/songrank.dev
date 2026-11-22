<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RankingsExport implements Export, WithMultipleSheets
{
    public function __construct(private Collection $rankings) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->rankings as $ranking) {
            $sheets[] = new SongExport($ranking->songs, $ranking->name);
        }

        return $sheets;
    }
}
