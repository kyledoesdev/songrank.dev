<?php

arch('System: Uses PHP preset')->preset()->php();
arch('System: Uses no debug methods')->expect(['dd', 'dump', 'die', 'ray'])->not->toBeUsed();
arch('System: Actions are final')->expect('App\Actions')->classes()->toBeFinal();
arch('System: Reads env only in config')->expect('env')->not->toBeUsed();
