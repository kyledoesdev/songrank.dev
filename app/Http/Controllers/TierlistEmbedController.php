<?php

namespace App\Http\Controllers;

use App\Models\Tierlist;
use App\Seo\TierlistPage;
use Illuminate\Contracts\View\View;
use Laravel\Head\Facades\Head;

class TierlistEmbedController extends Controller
{
    public function __invoke(string $id): View
    {
        $tierlist = Tierlist::query()->forEmbed()->findOrFail($id);

        (new TierlistPage)->handle($tierlist);

        Head::title($tierlist->name);
        Head::robots('noindex, follow');

        return view('tierlists.embed', ['tierlist' => $tierlist]);
    }
}
