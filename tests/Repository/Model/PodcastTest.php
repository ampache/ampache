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

namespace Ampache\Repository\Model;

use DateTime;
use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PodcastTest extends TestCase
{
    private Podcast $subject;

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function feedUrlDataProvider(): Generator
    {
        // Kept, because the feed has to be fetchable over http
        yield ['http://some-server.com/feed.xml', 'http://some-server.com/feed.xml'];
        yield ['https://some-server.com/feed.xml', 'https://some-server.com/feed.xml'];
        // Refused, leaving the previous value in place rather than storing something unfetchable
        yield ['ftp://some-server.com/feed.xml', ''];
        yield ['javascript:alert(1)', ''];
        yield ['some-value', ''];
        yield ['', ''];
    }

    public static function getterSetterDataProvider(): Generator
    {
        yield ['EpisodeCount', 0, 666];
        yield ['TotalCount', 0, 666];
        yield ['TotalSkip', 0, 666];
        yield ['Generator', '', 'some-value',];
        yield ['Website', '', 'some-value',];
        yield ['Copyright', '', 'some-value',];
        yield ['Language', '', 'chars',];
        yield ['Title', '', 'some-value',];
        yield ['Description', '', 'some-value',];
    }

    public function testGetCatalogIdReturnsSetValue(): void
    {
        $catalogId = 666;

        $catalog = $this->createMock(Catalog::class);

        $catalog->expects(static::once())
            ->method('getId')
            ->willReturn($catalogId);

        self::assertSame(
            0,
            $this->subject->getCatalogId()
        );

        $this->subject->setCatalog($catalog);

        self::assertSame(
            $catalogId,
            $this->subject->getCatalogId()
        );
    }

    public function testGetDefaultArtKindReturnsValue(): void
    {
        self::assertSame(
            'default',
            $this->subject->get_default_art_kind()
        );
    }

    public function testGetIdReturnsZeroOnNewObject(): void
    {
        self::assertSame(
            0,
            $this->subject->getId()
        );
    }

    public function testGetKeywordsReturnsKeywords(): void
    {
        $title = 'some-title';

        $this->subject->setTitle($title);

        self::assertSame(
            [
                'podcast' => [
                    'important' => true,
                    'label' => 'Podcast',
                    'value' => $title
                ]
            ],
            $this->subject->get_keywords()
        );
    }

    public function testGetLastBuildDateReturnsSetValue(): void
    {
        $data = new DateTime();

        $this->subject->setLastBuildDate($data);

        self::assertSame(
            $data->getTimestamp(),
            $this->subject->getLastBuildDate()->getTimestamp()
        );
    }

    public function testGetLastSyncDateReturnsSetValue(): void
    {
        $data = new DateTime();

        $this->subject->setLastSyncDate($data);

        self::assertSame(
            $data->getTimestamp(),
            $this->subject->getLastSyncDate()->getTimestamp()
        );
    }

    public function testGetParentReturnsNull(): void
    {
        self::assertNull(
            $this->subject->get_parent()
        );
    }

    public function testGetUserOwnerReturnsNull(): void
    {
        self::assertNull(
            $this->subject->get_user_owner()
        );
    }

    public function testIsNewReturnsTrueOnNewObject(): void
    {
        self::assertTrue(
            $this->subject->isNew()
        );
    }

    /**
     * Unlike the other setters this one validates, so it gets its own case
     */
    #[DataProvider(methodName: 'feedUrlDataProvider')]
    public function testSetFeedUrlKeepsOnlyHttpUrls(string $value, string $expectation): void
    {
        $this->subject->setFeedUrl($value);

        self::assertSame(
            $expectation,
            $this->subject->getFeedUrl()
        );
    }

    public function testSetFeedUrlKeepsThePreviousValueWhenRefused(): void
    {
        $feedUrl = 'https://some-server.com/feed.xml';

        $this->subject->setFeedUrl($feedUrl);
        $this->subject->setFeedUrl('not-a-url');

        self::assertSame(
            $feedUrl,
            $this->subject->getFeedUrl()
        );
    }

    public function testSetLanguageTruncates(): void
    {
        $value = 'söme-löng-value';

        $this->subject->setLanguage($value);

        self::assertSame(
            mb_substr($value, 0, 5),
            $this->subject->getLanguage()
        );
    }

    #[DataProvider(methodName: 'getterSetterDataProvider')]
    public function testStandardGetterSetterTest(
        string $methodName,
        mixed $default,
        mixed $value,
    ): void {
        self::assertSame(
            $default,
            call_user_func([$this->subject, 'get' . $methodName])
        );

        call_user_func([$this->subject, 'set' . $methodName], $value);

        self::assertSame(
            $value,
            call_user_func([$this->subject, 'get' . $methodName])
        );
    }

    public function testUpdateThrows(): void
    {
        $this->expectException(LogicException::class);

        $this->subject->update([]);
    }

    protected function setUp(): void
    {
        $this->subject = new Podcast();
    }
}
