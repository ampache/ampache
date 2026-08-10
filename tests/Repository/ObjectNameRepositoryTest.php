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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ObjectNameTypeEnum;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectNameRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private ObjectNameRepository $subject;

    /**
     * @return list<array{ObjectNameTypeEnum, string}>
     */
    public static function typeProvider(): array
    {
        return [
            [ObjectNameTypeEnum::ALBUM, '`album`.`prefix`'],
            [ObjectNameTypeEnum::ARTIST, '`artist`.`prefix`'],
            [ObjectNameTypeEnum::ALBUM_ARTIST, 'FROM `artist`'],
            [ObjectNameTypeEnum::SONG_ARTIST, 'FROM `artist`'],
            [ObjectNameTypeEnum::CATALOG, '`catalog`.`name`'],
            [ObjectNameTypeEnum::LIVE_STREAM, '`live_stream`.`name`'],
            [ObjectNameTypeEnum::PLAYLIST, '`playlist`.`name`'],
            [ObjectNameTypeEnum::SEARCH, '`search`.`name`'],
            [ObjectNameTypeEnum::PODCAST, '`podcast`.`title`'],
            [ObjectNameTypeEnum::PODCAST_EPISODE, '`podcast_episode`.`title`'],
            [ObjectNameTypeEnum::SONG, '`song`.`title`'],
            [ObjectNameTypeEnum::VIDEO, '`video`.`title`'],
            [ObjectNameTypeEnum::SHARE, '`share`.`description`'],
            [ObjectNameTypeEnum::PLAYLIST_SEARCH, "CONCAT('smart_', `id`)"],
        ];
    }

    public function testFindNamesBindsAPrefixedSmartlistIdRatherThanQuotingIt(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::stringContains('WHERE `id` IN (?,?);'),
                [3, 'smart_7']
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 'smart_7', 'name' => 'Some Smartlist'], false);

        static::assertSame(
            [['id' => 'smart_7', 'name' => 'Some Smartlist']],
            $this->subject->findNames(ObjectNameTypeEnum::PLAYLIST_SEARCH, [3, 'smart_7'])
        );
    }

    public function testFindNamesDropsASortThatIsNotABareColumn(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::logicalNot(static::stringContains('ORDER BY')), [1])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        $this->subject->findNames(ObjectNameTypeEnum::SONG, [1], 'name`; DROP TABLE `song');
    }

    public function testFindNamesForcesAnyOrderThatIsNotDescToAsc(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringEndsWith(' ORDER BY `name` ASC;'), [1])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        $this->subject->findNames(ObjectNameTypeEnum::SONG, [1], 'name', 'DESC; DROP TABLE `song`');
    }

    public function testFindNamesOrdersByBothColumnsOfACompoundSort(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringEndsWith(' ORDER BY `name` DESC, `original_year` DESC;'), [1])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        $this->subject->findNames(ObjectNameTypeEnum::ALBUM, [1], 'name_original_year', 'DESC');
    }

    #[DataProvider('typeProvider')]
    public function testFindNamesReadsTheNameColumnOfEveryType(ObjectNameTypeEnum $type, string $expected): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(static::stringContains($expected), [1])
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertSame([], $this->subject->findNames($type, [1]));
    }

    public function testFindNamesSkipsTheQueryForAnEmptyIdList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame([], $this->subject->findNames(ObjectNameTypeEnum::SONG, []));
    }

    public function testFindNamesUsesOnePlaceholderPerId(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `song`.`id`, `song`.`title` AS `name` FROM `song` WHERE `id` IN (?,?,?);',
                [1, 2, 3]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        $this->subject->findNames(ObjectNameTypeEnum::SONG, [1, 2, 3]);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new ObjectNameRepository($this->connection);
    }
}
