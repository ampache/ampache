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
use Ampache\Repository\Model\Label;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class LabelRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private LabelRepository $subject;

    public function testAddArtistAssocAdds(): void
    {
        $labelId  = 666;
        $artistId = 42;
        $date     = new DateTime();

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
                [$labelId, $artistId, $date->getTimestamp()]
            );

        $this->subject->addArtistAssoc($labelId, $artistId, $date);
    }

    public function testCollectGarbageDeletes(): void
    {
        // an association row names either an artist or an album, so each side is swept against its own table
        $this->connection->expects(static::exactly(4))
            ->method('query')
            ->with(...self::withConsecutive(
                ['DELETE FROM `label_asso` WHERE `label_asso`.`artist` IS NOT NULL AND `label_asso`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)'],
                ['DELETE FROM `label_asso` WHERE `label_asso`.`album` IS NOT NULL AND `label_asso`.`album` NOT IN (SELECT `album`.`id` FROM `album`)'],
                ['DELETE FROM `label_asso` WHERE `label_asso`.`label` NOT IN (SELECT `label`.`id` FROM `label`)'],
                ['DELETE FROM `label` WHERE `id` NOT IN (SELECT `label` FROM `label_asso`) AND `user` IS NULL'],
            ));

        $this->subject->collectGarbage();
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

    public function testGetAlbumsReturnsTheAssociatedIds(): void
    {
        $label  = $this->createMock(Label::class);
        $result = $this->createMock(PDOStatement::class);

        $label->method('getId')
            ->willReturn(666);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `album` FROM `label_asso` WHERE `label` = ? AND `album` IS NOT NULL', [666])
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(1, 2, false);

        self::assertSame([1, 2], $this->subject->getAlbums($label));
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

        self::assertSame(
            $this->subject->getAll(),
            [$labelId => $labelName]
        );
    }

    public function testGetArtistsReturnsTheAssociatedIds(): void
    {
        $label  = $this->createMock(Label::class);
        $result = $this->createMock(PDOStatement::class);

        $label->method('getId')
            ->willReturn(666);

        // an album row has a null artist, and fetching it would end the loop before the real ids arrive
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `artist` FROM `label_asso` WHERE `label` = ? AND `artist` IS NOT NULL', [666])
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(1, 2, false);

        self::assertSame([1, 2], $this->subject->getArtists($label));
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

        self::assertSame(
            $this->subject->getByArtist($artistId),
            [$labelId => $labelName]
        );
    }

    public function testGetIdsByCategoryAlsoTakesTheLabelsWithNoMbid(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `label` WHERE `category` = ? OR `mbid` IS NULL',
                ['tag_generated']
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn('42', false);

        self::assertSame([42], $this->subject->getIdsByCategory('tag_generated'));
    }

    public function testLookupReturnsNegativeValueOnEmptyName(): void
    {
        self::assertSame(
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

        self::assertSame(
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

        self::assertSame(
            0,
            $this->subject->lookup($labelName, $labelId)
        );
    }

    public function testMigrateAlbumMovesTheAssociationsAndDropsPairingsTheTargetAlreadyHas(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(...self::withConsecutive(
                [
                    'DELETE FROM `label_asso` WHERE `album` = ? AND `label` IN (SELECT `label` FROM (SELECT `label` FROM `label_asso` WHERE `album` = ?) AS `existing`)',
                    [21, 33],
                ],
                [
                    'UPDATE `label_asso` SET `album` = ? WHERE `album` = ?',
                    [33, 21],
                ],
            ));

        $this->subject->migrateAlbum(21, 33);
    }

    public function testMigrateArtistMovesTheAssociations(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('UPDATE `label_asso` SET `artist` = ? WHERE `artist` = ?', [33, 21]);

        $this->subject->migrateArtist(21, 33);
    }

    public function testPersistBindsAnInactiveLabelAsZeroRatherThanFalse(): void
    {
        $label = new Label();

        $label->id     = 666;
        $label->active = false;

        // PDO binds a bool false as '' and MySQL rejects that for the tinyint column, taking the
        // whole statement with it, so the flag has to reach the driver as an int
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::anything(),
                self::callback(static fn(array $params): bool => $params[8] === 0)
            );

        $this->subject->persist($label);
    }

    public function testPersistInsertsANewLabelAndReturnsTheId(): void
    {
        $label = new Label();

        $label->name          = 'some-name';
        $label->user          = 42;
        $label->active        = true;
        $label->creation_date = 1234;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('INSERT INTO `label`'),
                ['some-name', null, null, null, null, null, null, null, 42, 1, 1234]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(666, $this->subject->persist($label));
    }

    public function testPersistReturnsNullIfTheInsertYieldedNoId(): void
    {
        $label = new Label();

        $label->active = false;

        $this->connection->expects(static::once())
            ->method('query');

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(0);

        self::assertNull($this->subject->persist($label));
    }

    public function testPersistUpdatesAnExistingLabelAndReturnsNull(): void
    {
        $label = new Label();

        $label->id       = 666;
        $label->name     = 'some-name';
        $label->category = 'some-category';
        $label->active   = true;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::stringContains('UPDATE `label` SET `name` = ?'),
                ['some-name', null, 'some-category', null, null, null, null, null, 1, 666]
            );

        $this->connection->expects(static::never())
            ->method('getLastInsertedId');

        self::assertNull($this->subject->persist($label));
    }

    public function testRemoveArtistAssocDeletes(): void
    {
        $labelId  = 666;
        $artistId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
                [$labelId, $artistId]
            );

        $this->subject->removeArtistAssoc($labelId, $artistId);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new LabelRepository(
            $this->connection,
            $this->logger,
        );
    }
}
