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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class LabelRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private LabelRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new LabelRepository(
            $this->connection,
        );
    }

    public function testGetByArtistReturnsData(): void
    {
        $artistId  = 666;
        $labelId   = 42;
        $labelName = 'some-label';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?',
                [$artistId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => (string) $labelId, 'name' => $labelName], false);

        static::assertSame(
            $this->subject->getByArtist($artistId),
            [$labelId => $labelName]
        );
    }

    public function testGetAllReturnsData(): void
    {
        $labelId   = 42;
        $labelName = 'some-label';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `label`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => (string) $labelId, 'name' => $labelName], false);

        static::assertSame(
            $this->subject->getAll(),
            [$labelId => $labelName]
        );
    }

    public function testLookupReturnsNegativeValueOnEmptyName(): void
    {
        static::assertSame(
            -1,
            $this->subject->lookup(' ')
        );
    }

    public function testLookupReturnValueForLabelName(): void
    {
        $rowId     = 666;
        $labelName = 'some-name';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `label` WHERE `name` = ?', [$labelName])
            ->willReturn((string) $rowId);

        static::assertSame(
            $rowId,
            $this->subject->lookup($labelName)
        );
    }

    public function testLookupReturnValueForLabelNameAndNotId(): void
    {
        $labelId   = 42;
        $labelName = 'some-name';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `label` WHERE `name` = ? AND `id` != ?', [$labelName, $labelId])
            ->willReturn(false);

        static::assertSame(
            0,
            $this->subject->lookup($labelName, $labelId)
        );
    }

    public function testRemoveArtistAssocDeletes(): void
    {
        $labelId   = 666;
        $artistId  = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
                [$labelId, $artistId]
            );

        $this->subject->removeArtistAssoc($labelId, $artistId);
    }

    public function testAddArtistAssocAdds(): void
    {
        $labelId   = 666;
        $artistId  = 42;
        $date      = new DateTime();

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
                [$labelId, $artistId, $date->getTimestamp()]
            );

        $this->subject->addArtistAssoc($labelId, $artistId, $date);
    }

    public function testDeleteDeletes(): void
    {
        $labelId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `label` WHERE `id` = ?',
                [$labelId]
            );

        $this->subject->delete($labelId);
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(...self::withConsecutive(
                ['DELETE FROM `label_asso` WHERE `label_asso`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)'],
                ['DELETE FROM `label` WHERE `id` NOT IN (SELECT `label` FROM `label_asso`) AND `user` IS NULL'],
            ));

        $this->subject->collectGarbage();
    }
}
