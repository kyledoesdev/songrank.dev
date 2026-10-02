<?php

use App\Enums\ReviewType;
use App\Livewire\Reviews\Setup\AlbumSetup;
use App\Livewire\Reviews\Setup\ArtistSetup;
use App\Livewire\Reviews\Setup\TrackSetup;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Review;
use App\Models\Track;
use App\Models\User;
use Livewire\Livewire;

describe('confirming the start', function () {
    it('asks before creating anything', function () {
        $component = Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->set('subject', albumEntry())
            ->call('confirmStartReview');

        $confirm = alertsFrom($component, 'confirm')->first();

        expect($confirm['action'])->toBe('startReview')
            ->and($confirm['message'])->toBe("You're about to write about Currents. It stays a draft until you publish it.")
            ->and($confirm['confirmText'])->toBe("Let's go")
            ->and(Review::count())->toBe(0);
    });

    it('wants a subject first', function () {
        $component = Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->call('confirmStartReview');

        expect(alertsFrom($component)->pluck('title'))->toContain('Pick something to review first.')
            ->and(alertsFrom($component, 'confirm'))->toBeEmpty();
    });
});

describe('starting a review', function () {
    it('creates a draft and opens it for writing', function () {
        $user = kyle();

        $component = Livewire::actingAs($user)
            ->test(AlbumSetup::class)
            ->set('subject', albumEntry())
            ->set('form.name', 'Still holds up')
            ->set('form.is_public', '1')
            ->set('form.comments_enabled', '1')
            ->set('form.comments_replies_enabled', '0')
            ->call('startReview');

        $review = Review::sole();

        $component->assertRedirect(route('review.edit', ['id' => $review->getKey()]));

        expect($review->user_id)->toBe($user->getKey())
            ->and($review->type)->toBe(ReviewType::ALBUM)
            ->and($review->name)->toBe('Still holds up')
            ->and($review->is_published)->toBeFalse()
            ->and($review->published_at)->toBe('Not published yet')
            ->and($review->is_public)->toBeTrue()
            ->and($review->comments_enabled)->toBeTrue()
            ->and($review->comments_replies_enabled)->toBeFalse()
            ->and($review->body)->toBeNull()
            ->and($review->stars)->toBeNull();
    });

    it('names an unnamed review after its subject', function () {
        Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->set('subject', albumEntry())
            ->set('form.name', '')
            ->call('startReview');

        expect(Review::sole()->name)->toBe('Currents Review');
    });

    it('does nothing without a subject', function () {
        Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->call('startReview');

        expect(Review::count())->toBe(0);
    });
});

describe('resolving the subject', function () {
    it('stores an album with its credited artist', function () {
        Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->set('subject', albumEntry())
            ->call('startReview');

        $album = Review::sole()->subject;

        expect($album)->toBeInstanceOf(Album::class)
            ->and($album->album_id)->toBe('currents-id')
            ->and($album->name)->toBe('Currents')
            ->and(Artist::where('artist_id', 'tame-impala-id')->exists())->toBeTrue();
    });

    it('stores a track', function () {
        Livewire::actingAs(kyle())
            ->test(TrackSetup::class)
            ->set('subject', trackEntry())
            ->call('startReview');

        $track = Review::sole()->subject;

        expect($track)->toBeInstanceOf(Track::class)
            ->and($track->track_id)->toBe('less-i-know-id')
            ->and($track->album_name)->toBe('Currents');
    });

    it('stores an artist', function () {
        Livewire::actingAs(kyle())
            ->test(ArtistSetup::class)
            ->set('subject', artistEntry())
            ->call('startReview');

        $artist = Review::sole()->subject;

        expect($artist)->toBeInstanceOf(Artist::class)
            ->and($artist->artist_id)->toBe('tame-impala-id')
            ->and($artist->artist_img)->toBe('https://example.test/tame-impala.png');
    });

    it('fills in the picture of an artist first stored without one', function () {
        Artist::factory()->createOne(['artist_id' => 'tame-impala-id', 'artist_img' => null]);

        Livewire::actingAs(kyle())
            ->test(ArtistSetup::class)
            ->set('subject', artistEntry())
            ->call('startReview');

        expect(Artist::where('artist_id', 'tame-impala-id')->sole()->artist_img)
            ->toBe('https://example.test/tame-impala.png');
    });

    it('reuses a subject somebody else already reviewed', function () {
        foreach ([kyle(), User::factory()->createOne(['is_dev' => true])] as $user) {
            Livewire::actingAs($user)
                ->test(AlbumSetup::class)
                ->set('subject', albumEntry())
                ->call('startReview');
        }

        expect(Review::count())->toBe(2)
            ->and(Album::where('album_id', 'currents-id')->count())->toBe(1);
    });
});
