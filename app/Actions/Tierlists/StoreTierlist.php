<?php

namespace App\Actions\Tierlists;

use App\Enums\TierlistType;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreTierlist
{
    /**
     * One action for all three types. The only thing that differs between them
     * is which catalog row an entry resolves to, and ResolveTierlistEntries
     * already owns that.
     *
     * @param  array{type: TierlistType, entries: iterable, source?: ?Model, name?: ?string, is_public?: bool, comments_enabled?: bool, comments_replies_enabled?: bool}  $attributes
     */
    public function handle(User $user, array $attributes): Tierlist
    {
        return DB::transaction(function () use ($user, $attributes) {
            $type = $attributes['type'];
            $source = $attributes['source'] ?? null;

            /* The unique key on tierlist_items would reject a repeat, and a board
               with the same album on it twice was never what anybody asked for. */
            $entries = collect($attributes['entries'])->unique('id')->values();

            $tierlist = Tierlist::create([
                'user_id' => $user->getKey(),
                'type' => $type->value,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'name' => Str::limit($this->name($attributes, $type, $source), 30),
                'is_public' => $attributes['is_public'] ?? false,
                'comments_enabled' => $attributes['comments_enabled'] ?? false,
                'comments_replies_enabled' => $attributes['comments_replies_enabled'] ?? false,
            ]);

            (new CreateDefaultTiers)->handle($tierlist);

            $resolved = (new ResolveTierlistEntries)->handle($type, $entries);

            $bank = $tierlist->bank;
            $position = 0;
            $items = [];

            foreach ($entries as $entry) {
                $entryable = $resolved->get($entry['id']);

                if (is_null($entryable)) {
                    continue;
                }

                $items[] = [
                    'tierlist_id' => $tierlist->getKey(),
                    'tier_id' => $bank->getKey(),
                    'entryable_type' => $type->value,
                    'entryable_id' => $entryable->getKey(),
                    'position' => $position++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TierlistItem::insert($items);

            return $tierlist;
        });
    }

    /**
     * An unnamed list borrows the name of wherever its entries came from, and
     * falls back to its type when they came from nowhere in particular.
     */
    private function name(array $attributes, TierlistType $type, ?Model $source): string
    {
        $name = $attributes['name'] ?? null;

        if (filled($name)) {
            return $name;
        }

        if ($source) {
            return $source->name().' Tier List';
        }

        return 'My '.$type->label().' Tier List';
    }
}
