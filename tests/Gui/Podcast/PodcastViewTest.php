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

namespace Ampache\Gui\Podcast;

use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PodcastViewTest extends TestCase
{
    /**
     * The website comes from the feed, so anything that is not an http url is dropped rather than
     * rendered into an href.
     *
     * @return list<array{string, null|string}>
     */
    public static function websiteDataProvider(): array
    {
        return [
            ['https://example.com/show', 'https://example.com/show'],
            ['http://example.com/show', 'http://example.com/show'],
            ['', null],
            ['javascript:alert(1)', null],
            ['data:text/html;base64,PHN2Zz4=', null],
            ['//example.com', null],
            ['" onmouseover=alert(1) x="', null],
        ];
    }

    public function testGetArtSizeFollowsTheGridPreference(): void
    {
        self::assertSame(['width' => 150, 'height' => 150], $this->createSubject(gridView: true)->getArtSize());
        self::assertSame(['width' => 384, 'height' => 384], $this->createSubject(gridView: false)->getArtSize());
    }

    #[DataProvider('websiteDataProvider')]
    public function testGetWebsiteUrlOnlyAcceptsHttpUrls(string $website, ?string $expected): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getWebsite')->willReturn($website);

        self::assertSame($expected, $this->createSubject($podcast)->getWebsiteUrl());
    }

    /**
     * Deleting is a stricter right than managing, so the two must not collapse into one flag.
     */
    public function testPermissionsAreReportedIndependently(): void
    {
        $subject = $this->createSubject(mayInteract: true, mayManage: true, mayDelete: false);

        self::assertTrue($subject->mayInteract());
        self::assertTrue($subject->mayManage());
        self::assertFalse($subject->mayDelete());
    }

    public function testUrlsCarryThePodcastId(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getId')->willReturn(666);

        $subject = $this->createSubject($podcast);

        self::assertSame('some-path/podcast.php?action=delete&podcast_id=666', $subject->getDeleteUrl());
        self::assertSame('some-path/stats.php?action=graph&object_type=podcast&object_id=666', $subject->getGraphUrl());
    }

    private function createSubject(
        ?Podcast $podcast = null,
        bool $gridView = false,
        bool $mayInteract = false,
        bool $mayManage = false,
        bool $mayDelete = false,
    ): PodcastView {
        return new PodcastView(
            'some-path',
            $podcast ?? $this->createMock(Podcast::class),
            $this->createMock(Browse::class),
            [],
            $this->createMock(User::class),
            $gridView,
            false,
            false,
            false,
            false,
            $mayInteract,
            $mayManage,
            $mayDelete,
            false,
            false
        );
    }
}
