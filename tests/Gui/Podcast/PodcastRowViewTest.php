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

use Ampache\Repository\Model\Podcast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PodcastRowViewTest extends TestCase
{
    /**
     * The website comes from the feed's <link>, so anything that is not an http url is dropped rather than
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
            ['javascript:alert(document.cookie)', null],
            ['data:text/html;base64,PHN2Zz4=', null],
            ['//example.com', null],
            ['" onmouseover=alert(1) x="', null],
        ];
    }

    #[DataProvider('websiteDataProvider')]
    public function testGetWebsiteUrlOnlyAcceptsHttpUrls(string $website, ?string $expected): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getWebsite')->willReturn($website);

        $subject = new PodcastRowView(
            $podcast,
            'some-path',
            '',
            '',
            false,
            false,
            false,
            false,
            false,
            false,
        );

        self::assertSame($expected, $subject->getWebsiteUrl());
    }
}
