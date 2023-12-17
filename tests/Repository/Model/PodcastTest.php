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
 */

namespace Ampache\Repository\Model;

use DateTime;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PodcastTest extends TestCase
{
    private Podcast $subject;

    protected function setUp(): void
    {
        $this->subject = new Podcast();
    }

    public function testIsNewReturnsTrueOnNewObject(): void
    {
        static::assertTrue(
            $this->subject->isNew()
        );
    }

    public function testGetIdReturnsZeroOnNewObject(): void
    {
        static::assertSame(
            0,
            $this->subject->getId()
        );
    }

    public function testFormatDoesNothing(): void
    {
        static::expectNotToPerformAssertions();

        $this->subject->format();
    }

    public function testGetKeywordsReturnsKeywords(): void
    {
        $title = 'some-title';

        $this->subject->setTitle($title);

        static::assertSame(
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

    public function testGetParentReturnsNull(): void
    {
        static::assertNull(
            $this->subject->get_parent()
        );
    }

    public function testGetUserOwnerReturnsNull(): void
    {
        static::assertNull(
            $this->subject->get_user_owner()
        );
    }

    public function testGetDefaultArtKindReturnsValue(): void
    {
        static::assertSame(
            'default',
            $this->subject->get_default_art_kind()
        );
    }

    public static function getterSetterDataProvider(): Generator
    {
        yield ['EpisodeCount', 0, 666];
        yield ['TotalCount', 0, 666];
        yield ['TotalSkip', 0, 666];
        yield ['Generator', '', 'some-value',];
        yield ['Website', '', 'some-value',];
        yield ['Copyright', '', 'some-value',];
        yield ['Language', '', 'some-value',];
        yield ['FeedUrl', '', 'some-value',];
        yield ['Title', '', 'some-value',];
        yield ['Description', '', 'some-value',];
    }

    #[DataProvider(methodName: 'getterSetterDataProvider')]
    public function testStandardGetterSetterTest(
        string $methodName,
        mixed $default,
        mixed $value,
    ): void {
        static::assertSame(
            $default,
            call_user_func([$this->subject, 'get' . $methodName])
        );

        call_user_func([$this->subject, 'set' . $methodName], $value);

        static::assertSame(
            $value,
            call_user_func([$this->subject, 'get' . $methodName])
        );
    }

    public function testGetCatalogIdReturnsSetValue(): void
    {
        $catalogId = 666;

        $catalog = $this->createMock(Catalog::class);

        $catalog->expects(static::once())
            ->method('getId')
            ->willReturn($catalogId);

        static::assertSame(
            0,
            $this->subject->getCatalogId()
        );

        $this->subject->setCatalog($catalog);

        static::assertSame(
            $catalogId,
            $this->subject->getCatalogId()
        );
    }

    public function testGetLastSyncDateReturnsSetValue(): void
    {
        $data = new DateTime();

        $this->subject->setLastSyncDate($data);

        static::assertSame(
            $data->getTimestamp(),
            $this->subject->getLastSyncDate()->getTimestamp()
        );
    }

    public function testGetLastBuildDateReturnsSetValue(): void
    {
        $data = new DateTime();

        $this->subject->setLastBuildDate($data);

        static::assertSame(
            $data->getTimestamp(),
            $this->subject->getLastBuildDate()->getTimestamp()
        );
    }
}
