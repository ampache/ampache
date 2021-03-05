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

namespace Ampache\Module\Playlist\SearchType;

use Ampache\MockeryTestCase;
use Ampache\Repository\DatabaseTableNameEnum;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

class SearchTypeMapperTest extends MockeryTestCase
{
    /** @var MockInterface|ContainerInterface */
    private MockInterface $dic;

    private SearchTypeMapper $subject;

    public function setUp(): void
    {
        $this->dic = $this->mock(ContainerInterface::class);

        $this->subject = new SearchTypeMapper(
            $this->dic
        );
    }

    public function testMapReturnsNullIfTypeIsNotSUpported(): void
    {
        $this->assertNull(
            $this->subject->map('foobar')
        );
    }

    /**
     * @dataProvider searchTypeDataProvider
     */
    public function testMapMapsCallable(
        string $type,
        string $className
    ): void {
        $result = $this->mock(SearchTypeInterface::class);

        $this->dic->shouldReceive('get')
            ->with($className)
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->map($type)
        );
    }

    public function searchTypeDataProvider(): array
    {
        return [
            [DatabaseTableNameEnum::ALBUM, AlbumSearchType::class],
            [DatabaseTableNameEnum::ARTIST, ArtistSearchType::class],
            [DatabaseTableNameEnum::LABEL, LabelSearchType::class],
            [DatabaseTableNameEnum::PLAYLIST, PlaylistSearchType::class],
            [DatabaseTableNameEnum::SONG, SongSearchType::class],
            [DatabaseTableNameEnum::TAG, TagSearchType::class],
            [DatabaseTableNameEnum::USER, UserSearchType::class],
            [DatabaseTableNameEnum::VIDEO, VideoSearchType::class],
        ];
    }
}
