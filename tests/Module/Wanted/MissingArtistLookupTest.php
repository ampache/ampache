<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Wanted;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Cache\DatabaseObjectCacheInterface;
use Mockery\MockInterface;
use MusicBrainz\MusicBrainz;

class MissingArtistLookupTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var DatabaseObjectCacheInterface|MockInterface|null */
    private MockInterface $databaseObjectCache;

    /** @var MusicBrainz|MockInterface|null */
    private MockInterface $musicBrainz;

    private MissingArtistLookup $subject;

    public function setUp(): void
    {
        $this->configContainer     = $this->mock(ConfigContainerInterface::class);
        $this->databaseObjectCache = $this->mock(DatabaseObjectCacheInterface::class);
        $this->musicBrainz         = $this->mock(MusicBrainz::class);

        $this->subject = new MissingArtistLookup(
            $this->configContainer,
            $this->databaseObjectCache,
            $this->musicBrainz
        );
    }

    public function testLookupUsesDataFromCache(): void
    {
        $musicbrainzId = 'some-id';
        $name          = 'some-name';
        $webPath       = 'some-webpath';
        $link          = sprintf(
            '<a href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
            $webPath,
            $musicbrainzId,
            $name,
            $name
        );

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->databaseObjectCache->shouldReceive('retrieve')
            ->with('missing_artist', $musicbrainzId)
            ->once()
            ->andReturn([
                'mbid' => $musicbrainzId,
                'name' => $name
            ]);

        $this->assertSame(
            [
                'mbid' => $musicbrainzId,
                'name' => $name,
                'link' => $link,
            ],
            $this->subject->lookup($musicbrainzId)
        );
    }

    public function testLookupPerformsLookupAndReturnEmptyData(): void
    {
        $musicbrainzId = 'some-id';

        $this->databaseObjectCache->shouldReceive('retrieve')
            ->with('missing_artist', $musicbrainzId)
            ->once()
            ->andReturn([]);

        $this->musicBrainz->shouldReceive('lookup')
            ->with('artist', $musicbrainzId)
            ->once()
            ->andThrow(new \Exception());

        $this->assertSame(
            [
                'mbid' => $musicbrainzId,
                'name' => 'Unknown Artist',
            ],
            $this->subject->lookup($musicbrainzId)
        );
    }

    public function testLookupPerformsLookupAndReturnsFinding(): void
    {
        $musicbrainzId = 'some-id';
        $name          = 'some-name';
        $webPath       = 'some-webpath';
        $link          = sprintf(
            '<a href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
            $webPath,
            $musicbrainzId,
            $name,
            $name
        );

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->databaseObjectCache->shouldReceive('retrieve')
            ->with('missing_artist', $musicbrainzId)
            ->once()
            ->andReturn([]);
        $this->databaseObjectCache->shouldReceive('add')
            ->with('missing_artist', $musicbrainzId, ['mbid' => $musicbrainzId, 'name' => $name])
            ->once();

        $this->musicBrainz->shouldReceive('lookup')
            ->with('artist', $musicbrainzId)
            ->once()
            ->andReturn((object) [
                'name' => $name,
            ]);

        $this->assertSame(
            [
                'mbid' => $musicbrainzId,
                'name' => $name,
                'link' => $link,
            ],
            $this->subject->lookup($musicbrainzId)
        );
    }
}
