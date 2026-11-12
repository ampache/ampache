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

namespace Ampache\Module\Art\Collector;

use Ampache\MockeryTestCase;
use Ampache\Module\Art\Art;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Log\LoggerInterface;

class MusicbrainzCollectorModuleTest extends MockeryTestCase
{
    private const string GROUP   = 'f5093c06-23e3-404f-aeaa-40f72885ee3a';
    private const string RELEASE = '76df3287-6cda-33eb-8e9a-044b5e15ffdd';

    private MusicbrainzCollectorModule $subject;
    private WebFetcherInterface&MockInterface $webFetcher;

    public function testCollectArtFallsBackToTheReleaseGroup(): void
    {
        $this->webFetcher->shouldReceive('fetch')
            ->with('https://coverartarchive.org/release/' . self::RELEASE)
            ->once()
            ->andThrow(new FetchFailedException('404'));
        $this->webFetcher->shouldReceive('fetch')
            ->with('https://coverartarchive.org/release-group/' . self::GROUP)
            ->once()
            ->andReturn('{"images":[{"front":true,"image":"https://coverartarchive.org/release/y/9.jpg"}]}');

        self::assertSame(
            [['url' => 'https://coverartarchive.org/release/y/9.jpg', 'mime' => 'image/jpeg', 'title' => 'Cover Art Archive']],
            $this->subject->collectArt(
                $this->createArt('album'),
                5,
                ['mb_albumid' => self::RELEASE, 'mb_albumid_group' => self::GROUP]
            )
        );
    }

    public function testCollectArtIgnoresNonAlbumTypes(): void
    {
        $this->webFetcher->shouldReceive('fetch')->never();

        self::assertSame([], $this->subject->collectArt($this->createArt('artist'), 5, ['mb_albumid' => self::RELEASE]));
    }

    public function testCollectArtPutsFrontCoversFirstAndHonorsTheLimit(): void
    {
        $response = json_encode([
            'images' => [
                [
                    'front' => false,
                    'image' => 'http://coverartarchive.org/release/x/1.png',
                    'thumbnails' => [],
                ],
                [
                    'front' => true,
                    'image' => 'http://coverartarchive.org/release/x/2.jpg',
                    'thumbnails' => ['1200' => 'http://coverartarchive.org/release/x/2-1200.jpg'],
                ],
                [
                    'front' => false,
                    'image' => 'http://coverartarchive.org/release/x/3.jpg',
                    'thumbnails' => [],
                ],
            ],
        ]);

        $this->webFetcher->shouldReceive('fetch')
            ->with('https://coverartarchive.org/release/' . self::RELEASE)
            ->once()
            ->andReturn($response);

        self::assertSame(
            [
                ['url' => 'https://coverartarchive.org/release/x/2-1200.jpg', 'mime' => 'image/jpeg', 'title' => 'Cover Art Archive'],
                ['url' => 'https://coverartarchive.org/release/x/1.png', 'mime' => 'image/png', 'title' => 'Cover Art Archive'],
            ],
            $this->subject->collectArt($this->createArt('album'), 2, ['mb_albumid' => strtoupper(self::RELEASE)])
        );
    }

    public function testCollectArtRefusesAnMbidThatIsNotAUuid(): void
    {
        $this->webFetcher->shouldReceive('fetch')->never();

        self::assertSame([], $this->subject->collectArt($this->createArt('album'), 5, ['mb_albumid' => '../../evil']));
    }

    public function testCollectArtReturnsNothingForAnUnreadableResponse(): void
    {
        $this->webFetcher->shouldReceive('fetch')->once()->andReturn('<html>');

        self::assertSame([], $this->subject->collectArt($this->createArt('album'), 5, ['mb_albumid' => self::RELEASE]));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->webFetcher = $this->mock(WebFetcherInterface::class);

        $this->subject = new MusicbrainzCollectorModule(
            $this->webFetcher,
            $this->mock(LoggerInterface::class)->shouldIgnoreMissing(),
        );
    }

    private function createArt(string $objectType): Art
    {
        $art              = Mockery::mock(Art::class);
        $art->object_type = $objectType;

        return $art;
    }
}
