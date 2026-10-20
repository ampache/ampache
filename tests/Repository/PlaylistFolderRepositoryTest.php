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
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class PlaylistFolderRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private const int USER_ID = 42;

    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private PlaylistFolderRepository $subject;
    private User&MockObject $user;

    public function testCollectGarbageRunsTheRestOfTheSweepAfterAFailedStatement(): void
    {
        $calls = 0;

        $this->connection->expects(static::atLeast(2))
            ->method('query')
            ->willReturnCallback(function () use (&$calls): PDOStatement {
                $calls++;

                if ($calls === 1) {
                    throw new QueryFailedException();
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->logger->expects(static::once())
            ->method('debug');

        $this->subject->collectGarbage();

        self::assertGreaterThan(1, $calls);
    }

    public function testCreateAppendsAfterTheLastSiblingAcrossBothTables(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT MAX(`sort_order`) FROM (SELECT `sort_order` FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? UNION ALL SELECT `sort_order` FROM `playlist_folder_map` WHERE `user` = ? AND `folder` = ?) AS `siblings`;',
                [self::USER_ID, PlaylistFolder::ROOT, self::USER_ID, PlaylistFolder::ROOT]
            )
            ->willReturn('4');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `playlist_folder` (`user`, `parent`, `name`, `sort_order`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?, ?);',
                self::callback(
                    static fn(array $params): bool => $params[0] === self::USER_ID
                        && $params[1] === PlaylistFolder::ROOT
                        && $params[2] === 'Rock'
                        && $params[3] === 5
                        && $params[4] === 'private'
                )
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(7);

        self::assertSame(7, $this->subject->create($this->user, 'Rock'));
    }

    public function testCreateDefaultsAnInvalidTypeToPrivate(): void
    {
        $this->connection->method('fetchOne')
            ->willReturn('0');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `playlist_folder` (`user`, `parent`, `name`, `sort_order`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?, ?);',
                self::callback(static fn(array $params): bool => $params[4] === 'private')
            );

        $this->connection->method('getLastInsertedId')
            ->willReturn(7);

        self::assertSame(7, $this->subject->create($this->user, 'Rock', type: 'bogus'));
    }

    public function testCreateRefusesANameThatCannotBeAddressedByPath(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertNull($this->subject->create($this->user, 'Rock/Live'));
    }

    public function testCreateRefusesAParentBelongingToAnotherUser(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `playlist_folder` WHERE `id` = ? AND `user` = ?;',
                [9, self::USER_ID]
            )
            ->willReturn(null);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertNull($this->subject->create($this->user, 'Live', 9));
    }

    /**
     * A sibling name collides case-insensitively under the unicode collation, and the insert reports it
     */
    public function testCreateReturnsNullWhenTheUniqueKeyRejectsTheName(): void
    {
        $this->connection->method('fetchOne')
            ->willReturn('0');

        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException());

        self::assertNull($this->subject->create($this->user, 'rock'));
    }

    public function testDeleteRefusesAFolderThatStillHoldsAChildFolder(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM `playlist_folder` WHERE `parent` = ?;', [7])
            ->willReturn('1');

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->delete(7));
    }

    public function testDeleteRefusesAFolderThatStillHoldsAPlacement(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with(
                ...self::withConsecutive(
                    ['SELECT COUNT(*) FROM `playlist_folder` WHERE `parent` = ?;', [7]],
                    ['SELECT COUNT(*) FROM `playlist_folder_map` WHERE `folder` = ?;', [7]]
                )
            )
            ->willReturn('0', '2');

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->delete(7));
    }

    public function testDeleteRemovesAnEmptyFolder(): void
    {
        $this->connection->method('fetchOne')
            ->willReturn('0', '0');

        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `playlist_folder` WHERE `id` = ?;', [7]);

        self::assertTrue($this->subject->delete(7));
    }

    public function testFindByPathRefusesAnEmptyOrOverDeepPath(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        self::assertNull($this->subject->findByPath($this->user, '/'));
        self::assertNull($this->subject->findByPath($this->user, ''));
        self::assertNull($this->subject->findByPath($this->user, str_repeat('/a', 33)));
    }

    public function testFindByPathStopsAtTheFirstMissingSegment(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(null);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertNull($this->subject->findByPath($this->user, '/Rock/Live'));
    }

    public function testFindByPathWalksOneSegmentAtATime(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with(
                ...self::withConsecutive(
                    [
                        'SELECT `id` FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? AND `name` = ?;',
                        [self::USER_ID, PlaylistFolder::ROOT, 'Rock'],
                    ],
                    [
                        'SELECT `id` FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? AND `name` = ?;',
                        [self::USER_ID, 3, 'Live'],
                    ]
                )
            )
            ->willReturn('3', '7');

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `playlist_folder` WHERE `id` = ?;', [7])
            ->willReturn($this->rowStatement(['id' => '7', 'user' => '42', 'parent' => '3', 'name' => 'Live']));

        $folder = $this->subject->findByPath($this->user, '/Rock/Live');

        self::assertNotNull($folder);
        self::assertSame(7, $folder->getId());
    }

    public function testGetChildrenHydratesFromTheRowsInOneQuery(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? ORDER BY `sort_order`, `name`;',
                [self::USER_ID, PlaylistFolder::ROOT]
            )
            ->willReturn(
                $this->rowStatement(
                    ['id' => '3', 'user' => '42', 'parent' => '0', 'name' => 'Rock', 'sort_order' => '1'],
                    ['id' => '4', 'user' => '42', 'parent' => '0', 'name' => 'Jazz', 'sort_order' => '2']
                )
            );

        $children = $this->subject->getChildren($this->user);

        self::assertCount(2, $children);
        self::assertSame(['Rock', 'Jazz'], array_map(static fn(PlaylistFolder $folder): string => $folder->getName(), $children));
    }

    public function testGetHiddenFoldersJoinsBackToTheirOwnRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `playlist_folder`.* FROM `playlist_folder` INNER JOIN `playlist_folder_hide` ON `playlist_folder_hide`.`folder` = `playlist_folder`.`id` WHERE `playlist_folder_hide`.`user` = ? ORDER BY `playlist_folder`.`user`, `playlist_folder`.`sort_order`, `playlist_folder`.`name`;',
                [self::USER_ID]
            )
            ->willReturn($this->rowStatement(['id' => '11', 'user' => '20', 'parent' => '0', 'name' => 'Metal', 'type' => 'public']));

        $hidden = $this->subject->getHiddenFolders(self::USER_ID);

        self::assertCount(1, $hidden);
        self::assertSame('Metal', $hidden[0]->getName());
    }

    public function testGetPlacedObjectIdsSkipsTheRoot(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `object_id` FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `folder` != ?;',
                [self::USER_ID, 'playlist', PlaylistFolder::ROOT]
            )
            ->willReturn($result);

        $result->method('fetchColumn')
            ->willReturn('12', '13', false);

        self::assertSame([12, 13], $this->subject->getPlacedObjectIds($this->user, 'playlist'));
    }

    public function testGetPlacementNormalisesTheApiSpellingOfASmartlist(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `folder`, `sort_order` FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?;',
                [self::USER_ID, 'search', 12]
            )
            ->willReturn($this->rowStatement(['folder' => '3', 'sort_order' => '2']));

        self::assertSame(
            ['folder' => 3, 'sort_order' => 2],
            $this->subject->getPlacement($this->user, 12, 'smartlist')
        );
    }

    public function testGetPlacementReturnsNullWhenTheListWasNeverFiled(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willReturn($this->rowStatement());

        self::assertNull($this->subject->getPlacement($this->user, 12, 'playlist'));
    }

    /**
     * Filing at the root with no position is the absence of a row, which is what keeps an unfiled list free
     */
    public function testGetPublicChildrenFiltersToPublicRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                "SELECT * FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? AND `type` = 'public' ORDER BY `sort_order`, `name`;",
                [9, PlaylistFolder::ROOT]
            )
            ->willReturn($this->rowStatement(['id' => '3', 'user' => '9', 'parent' => '0', 'name' => 'Rock', 'type' => 'public']));

        $children = $this->subject->getPublicChildren(9, PlaylistFolder::ROOT);

        self::assertCount(1, $children);
        self::assertSame('Rock', $children[0]->getName());
    }

    public function testGetPublicRootFoldersIncludesFoldersTheViewerHasHidden(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                self::callback(static fn(string $sql): bool => !str_contains($sql, 'playlist_folder_hide')),
                [self::USER_ID]
            )
            ->willReturn($this->rowStatement(['id' => '5', 'user' => '9', 'parent' => '0', 'name' => 'Jazz', 'type' => 'public']));

        $shared = $this->subject->getPublicRootFolders(self::USER_ID);

        self::assertCount(1, $shared);
        self::assertSame(9, $shared[0]->getUserId());
    }

    public function testHideUpsertsSoHidingTwiceStaysOneRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `playlist_folder_hide` (`user`, `folder`, `date`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `date` = VALUES(`date`);',
                self::callback(static fn(array $params): bool => $params[0] === self::USER_ID && $params[1] === 11)
            );

        $this->subject->hide(self::USER_ID, 11);
    }

    public function testIsReadableByAllowsTheViewersOwnFolderRegardlessOfItsType(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `user`, `type`, `parent` FROM `playlist_folder` WHERE `id` = ?;', [3])
            ->willReturn($this->rowStatement(['user' => '9', 'type' => 'private', 'parent' => '0']));

        self::assertTrue($this->subject->isReadableBy(3, 9));
    }

    public function testIsReadableByFollowsAPublicChainToTheRoot(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->with(
                ...self::withConsecutive(
                    ['SELECT `user`, `type`, `parent` FROM `playlist_folder` WHERE `id` = ?;', [7]],
                    ['SELECT `user`, `type`, `parent` FROM `playlist_folder` WHERE `id` = ?;', [3]]
                )
            )
            ->willReturn(
                $this->rowStatement(['user' => '9', 'type' => 'public', 'parent' => '3']),
                $this->rowStatement(['user' => '9', 'type' => 'public', 'parent' => '0'])
            );

        self::assertTrue($this->subject->isReadableBy(7, 2));
    }

    public function testIsReadableByRefusesADanglingId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `user`, `type`, `parent` FROM `playlist_folder` WHERE `id` = ?;', [7])
            ->willReturn($this->rowStatement());

        self::assertFalse($this->subject->isReadableBy(7, 2));
    }

    public function testIsReadableByStopsAtAPrivateLink(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `user`, `type`, `parent` FROM `playlist_folder` WHERE `id` = ?;', [7])
            ->willReturn($this->rowStatement(['user' => '9', 'type' => 'private', 'parent' => '3']));

        self::assertFalse($this->subject->isReadableBy(7, 2));
    }

    public function testIsReadableByTreatsTheRootAsAlwaysReadable(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertTrue($this->subject->isReadableBy(PlaylistFolder::ROOT, 2));
    }

    public function testPlaceAtTheRootWithoutAPositionRemovesTheRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?;',
                [self::USER_ID, 'playlist', 12]
            );

        self::assertTrue($this->subject->place($this->user, 12, 'playlist', null));
    }

    public function testPlaceRefusesAFolderBelongingToAnotherUser(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn(null);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->place($this->user, 12, 'playlist', 3));
    }

    public function testPlaceRefusesATypeAFolderCannotHold(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->place($this->user, 12, 'song', 3));
    }

    public function testPlaceUpsertsSoASecondFilingMovesRatherThanDuplicates(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `playlist_folder` WHERE `id` = ? AND `user` = ?;',
                [3, self::USER_ID]
            )
            ->willReturn('3');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `playlist_folder_map` (`user`, `folder`, `object_id`, `object_type`, `sort_order`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `folder` = VALUES(`folder`), `sort_order` = VALUES(`sort_order`);',
                [self::USER_ID, 3, 12, 'search', 5]
            );

        self::assertTrue($this->subject->place($this->user, 12, 'smartlist', 3, 5));
    }

    public function testUnhideRemovesTheDismissal(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `playlist_folder_hide` WHERE `user` = ? AND `folder` = ?;',
                [self::USER_ID, 11]
            );

        $this->subject->unhide(self::USER_ID, 11);
    }

    public function testUpdateIgnoresAnInvalidType(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `user` FROM `playlist_folder` WHERE `id` = ?;', [3])
            ->willReturn((string) self::USER_ID);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->update(3, type: 'bogus'));
    }

    public function testUpdateRefusesAMoveIntoItsOwnDescendant(): void
    {
        // owner lookup, then the ancestor walk from the proposed parent back up to the folder itself
        $this->connection->expects(static::exactly(3))
            ->method('fetchOne')
            ->with(
                ...self::withConsecutive(
                    ['SELECT `user` FROM `playlist_folder` WHERE `id` = ?;', [3]],
                    ['SELECT `parent` FROM `playlist_folder` WHERE `id` = ?;', [9]],
                    ['SELECT `parent` FROM `playlist_folder` WHERE `id` = ?;', [7]]
                )
            )
            ->willReturn((string) self::USER_ID, '7', '3');

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->update(3, null, 9));
    }

    public function testUpdateRefusesMakingAFolderItsOwnParent(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `user` FROM `playlist_folder` WHERE `id` = ?;', [3])
            ->willReturn((string) self::USER_ID);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->update(3, null, 3));
    }

    public function testUpdateWithNothingToChangeWritesNothing(): void
    {
        $this->connection->method('fetchOne')
            ->willReturn((string) self::USER_ID);

        $this->connection->expects(static::never())
            ->method('query');

        self::assertFalse($this->subject->update(3));
    }

    public function testUpdateWritesOnlyTheFieldsSupplied(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `user` FROM `playlist_folder` WHERE `id` = ?;', [3])
            ->willReturn((string) self::USER_ID);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `playlist_folder` SET `name` = ?, `last_update` = ? WHERE `id` = ?;',
                self::callback(
                    static fn(array $params): bool => $params[0] === 'Rock' && $params[2] === 3
                )
            );

        self::assertTrue($this->subject->update(3, 'Rock'));
    }

    public function testUpdateWritesTheTypeWhenValid(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `user` FROM `playlist_folder` WHERE `id` = ?;', [3])
            ->willReturn((string) self::USER_ID);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `playlist_folder` SET `type` = ?, `last_update` = ? WHERE `id` = ?;',
                self::callback(static fn(array $params): bool => $params[0] === 'public' && $params[2] === 3)
            );

        self::assertTrue($this->subject->update(3, type: 'public'));
    }

    public function testWouldCycleAcceptsAnUnrelatedParent(): void
    {
        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with(
                ...self::withConsecutive(
                    ['SELECT `parent` FROM `playlist_folder` WHERE `id` = ?;', [9]],
                    ['SELECT `parent` FROM `playlist_folder` WHERE `id` = ?;', [8]]
                )
            )
            ->willReturn('8', '0');

        self::assertFalse($this->subject->wouldCycle(3, 9));
    }

    public function testWouldCycleSurvivesAnAlreadyCyclicTree(): void
    {
        // a broken tree must terminate the walk rather than loop until the request dies
        $this->connection->method('fetchOne')
            ->willReturn('9', '8', '9', '8');

        self::assertFalse($this->subject->wouldCycle(3, 8));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);
        $this->user       = $this->createMock(User::class);

        $this->user->method('getId')
            ->willReturn(self::USER_ID);

        $this->subject = new PlaylistFolderRepository(
            $this->connection,
            $this->logger
        );
    }

    /**
     * Rows a mocked statement hands back, followed by the `false` that ends a fetch loop
     *
     * @param array<string, string> ...$rows
     */
    private function rowStatement(array ...$rows): PDOStatement&MockObject
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(...[...$rows, false]);

        return $statement;
    }
}
