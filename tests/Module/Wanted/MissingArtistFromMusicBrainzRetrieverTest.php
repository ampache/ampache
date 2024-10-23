<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Wanted;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\LegacyLogger;
use Exception;
use MusicBrainz\MusicBrainz;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

#[RunTestsInSeparateProcesses]
class MissingArtistFromMusicBrainzRetrieverTest extends TestCase
{
    private MusicBrainz&MockObject $musicBrainz;

    private CacheInterface&MockObject $cache;

    private LoggerInterface&MockObject $logger;

    private MissingArtistFromMusicBrainzRetriever $subject;

    private string $mbid = '12345-foobar';

    protected function setup(): void
    {
        $this->musicBrainz = $this->createMock(MusicBrainz::class);
        $this->cache       = $this->createMock(CacheInterface::class);
        $this->logger      = $this->createMock(LoggerInterface::class);

        $this->subject = new MissingArtistFromMusicBrainzRetriever(
            $this->musicBrainz,
            $this->cache,
            $this->logger,
        );
    }

    public function testRetrieveReturnsNullIfMbidIsInvalid(): void
    {
        static::assertNull(
            $this->subject->retrieve(' ')
        );
    }

    public function testRetrieveReturnsCachedItem(): void
    {
        $item = ['some-item'];

        $this->cache->expects(static::once())
            ->method('get')
            ->with(sprintf('wanted:artist:%s', $this->mbid))
            ->willReturn($item);

        static::assertSame(
            $item,
            $this->subject->retrieve($this->mbid)
        );
    }

    public function testRetrieveCatchesServiceErrorAndReturnsDefaultResult(): void
    {
        $errorMessage = 'some baz error';

        $this->cache->expects(static::once())
            ->method('get')
            ->with(sprintf('wanted:artist:%s', $this->mbid))
            ->willReturn(null);

        $this->musicBrainz->expects(static::once())
            ->method('lookup')
            ->with('artist', $this->mbid)
            ->willThrowException(new Exception($errorMessage));

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf(
                    'Error retrieving MusicBrainz info for artist `%s`: %s',
                    $this->mbid,
                    $errorMessage
                ),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        static::assertSame(
            [
                'mbid' => $this->mbid,
                'name' => 'Unknown Artist',
                'link' => '',
            ],
            $this->subject->retrieve($this->mbid)
        );
    }

    public function testRetrieveReturnsDefaultResultIfMbidIsNotKnown(): void
    {
        $defaultItem = [
            'mbid' => $this->mbid,
            'name' => 'Unknown Artist',
            'link' => '',
        ];

        $this->cache->expects(static::once())
            ->method('get')
            ->with(sprintf('wanted:artist:%s', $this->mbid))
            ->willReturn(null);
        $this->cache->expects(static::once())
            ->method('set')
            ->with(sprintf('wanted:artist:%s', $this->mbid), $defaultItem);

        $this->musicBrainz->expects(static::once())
            ->method('lookup')
            ->with('artist', $this->mbid)
            ->willReturn((object) ['error' => 'some-error']);

        static::assertSame(
            $defaultItem,
            $this->subject->retrieve($this->mbid)
        );
    }

    public function testRetrieveReturnsResult(): void
    {
        $artistName  = 'some-name';
        $defaultItem = [
            'mbid' => $this->mbid,
            'name' => $artistName,
            'link' => sprintf(
                '<a href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
                AmpConfig::get_web_path(),
                $this->mbid,
                $artistName,
                $artistName
            ),
        ];

        $this->cache->expects(static::once())
            ->method('get')
            ->with(sprintf('wanted:artist:%s', $this->mbid))
            ->willReturn(null);
        $this->cache->expects(static::once())
            ->method('set')
            ->with(sprintf('wanted:artist:%s', $this->mbid), $defaultItem);

        $this->musicBrainz->expects(static::once())
            ->method('lookup')
            ->with('artist', $this->mbid)
            ->willReturn((object) ['name' => $artistName]);

        static::assertSame(
            $defaultItem,
            $this->subject->retrieve($this->mbid)
        );
    }
}
