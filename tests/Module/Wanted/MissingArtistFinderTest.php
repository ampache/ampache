<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Wanted;

use Mockery;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use MusicBrainz\Filters\ArtistFilter;
use MusicBrainz\MusicBrainz;

class MissingArtistFinderTest extends MockeryTestCase
{
    /** @var MockInterface|MusicBrainz|null */
    private MockInterface $musicBrainz;

    private ?MissingArtistFinder $subject;

    protected function setUp(): void
    {
        $this->musicBrainz = $this->mock(MusicBrainz::class);

        $this->subject = new MissingArtistFinder(
            $this->musicBrainz
        );
    }

    public function testFindReturnsFinds(): void
    {
        $artistName    = 'some-artist-name';
        $musicBrainzId = 'some-mbid';
        $name          = 'some-name';

        $this->musicBrainz->shouldReceive('search')
            ->with(Mockery::type(ArtistFilter::class))
            ->once()
            ->andReturn([(object) ['id' => $musicBrainzId, 'name' => $name]]);

        $this->assertSame(
            [['mbid' => $musicBrainzId, 'name' => $name]],
            $this->subject->find($artistName)
        );
    }
}
