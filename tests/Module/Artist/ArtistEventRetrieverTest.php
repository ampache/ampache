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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Artist;
use Ampache\Module\LastFm\LastFmQueryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use SimpleXMLElement;

class ArtistEventRetrieverTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private ConfigContainerInterface $configContainer;

    /** @var LastFmQueryInterface|MockInterface|null */
    private LastFmQueryInterface $lastFmQuery;
    
    /** @var ArtistEventRetriever|null */
    private ?ArtistEventRetriever  $subject;

    public function setUp(): void
    {
        $this->configContainer = Mockery::mock(ConfigContainerInterface::class);
        $this->lastFmQuery     = Mockery::mock(LastFmQueryInterface::class);
        
        $this->subject = new ArtistEventRetriever(
            $this->configContainer,
            $this->lastFmQuery
        );
    }
    
    public function testGetUpcomingEventsReturnsEmptyArrayIfNoEventsFound(): void
    {
        $limit_future  = 666;
        $mbid          = 42;
        $lastfm_result = '<dom></dom>';
        
        $artist = Mockery::mock(Artist::class);
        $result = new SimpleXMLElement($lastfm_result);
        
        $artist->mbid = $mbid;
        
        $query = sprintf(
            'mbid=%d&limit=%d',
            $mbid,
            $limit_future
        );
        
        $this->configContainer->shouldReceive('get')
            ->with('concerts_limit_future')
            ->once()
            ->andReturn($limit_future);
        
        $this->lastFmQuery->shouldReceive('getLastFmResults')
            ->with('artist.getevents', $query)
            ->once()
            ->andReturn($result);
        
        static::assertSame(
            [],
            $this->subject->getUpcomingEvents($artist)
        );
    }

    public function testGetPastEventsSearchesWitNameAndReturnsData(): void
    {
        $limit         = 666;
        $name          = 'some-artist-name';
        $event         = 'some-event';
        $lastfm_result = '<dom><events><event>' . $event . '</event></events></dom>';

        $artist = Mockery::mock(Artist::class);
        $result = new SimpleXMLElement($lastfm_result);

        $artist->name = $name;

        $query = sprintf(
            'artist=%s&limit=%d',
            $name,
            $limit
        );

        $this->configContainer->shouldReceive('get')
            ->with('concerts_limit_past')
            ->once()
            ->andReturn($limit);

        $this->lastFmQuery->shouldReceive('getLastFmResults')
            ->with('artist.getpastevents', $query)
            ->once()
            ->andReturn($result);

        static::assertInstanceOf(
            SimpleXMLElement::class,
            current($this->subject->getPastEvents($artist))
        );
    }
}
