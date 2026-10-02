<?php

namespace App\Http\Controllers;

use App\Actions\Tierlists\ApplyTierlistShareTags;
use App\Models\Tierlist;
use Illuminate\Contracts\View\View;
use Laravel\Head\Facades\Head;

class TierlistEmbedController extends Controller
{
    public function __invoke(int $id): View
    {
        $tierlist = Tierlist::query()->forEmbed()->findOrFail($id);

        (new ApplyTierlistShareTags)->handle($tierlist);

        Head::title($tierlist->name);
        Head::robots('noindex, follow');

        return view('tierlists.embed', ['tierlist' => $tierlist]);
    }
}
