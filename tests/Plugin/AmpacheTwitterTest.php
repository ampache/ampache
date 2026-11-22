<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Plugin;

use PHPUnit\Framework\TestCase;

class AmpacheTwitterTest extends TestCase
{
    // the old twitter.com share page only bounces through a redirect, x.com opens the post box directly
    public function testThePostBoxOpensWithTheLinkAndTheText(): void
    {
        self::assertSame(
            'https://x.com/intent/post?url=https%3A%2F%2Fmusic.example%2Fsong.php%3Fid%3D7&text=April%20Showers',
            new AmpacheTwitter()->external_share('https://music.example/song.php?id=7', 'April Showers')
        );
    }
}
