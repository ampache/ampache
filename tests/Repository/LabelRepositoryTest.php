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

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery;
use Mockery\MockInterface;

class LabelRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|Connection */
    private MockInterface $database;

    private LabelRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new LabelRepository(
            $this->database
        );
    }

    public function testGetByArtistReturnsData(): void
    {
        $artistId  = 666;
        $labelId   = 42;
        $labelName = 'some-label';

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?',
                [$artistId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->times(2)
            ->andReturn(['id' => (string) $labelId, 'name' => $labelName], false);

        $this->assertSame(
            [$labelId => $labelName],
            $this->subject->getByArtist($artistId)
        );
    }

    public function testGetAllReturnsListOfLabels(): void
    {
        $labelId   = 42;
        $labelName = 'some-label';

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id`, `name` FROM `label`'
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->times(2)
            ->andReturn(['id' => (string) $labelId, 'name' => $labelName], false);

        $this->assertSame(
            [$labelId => $labelName],
            $this->subject->getAll()
        );
    }

    public function testLookupReturnsNegativeValueIfNameIsEmpty(): void
    {
        $this->assertSame(
            -1,
            $this->subject->lookup('')
        );
    }

    public function testLookupReturnsLabelId(): void
    {
        $labelName = 'some-label';
        $labelId   = 666;
        $foundId   = 42;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `label` WHERE `name` = ? AND `id` != ?',
                [$labelName, $labelId]
            )
            ->once()
            ->andReturn((string) $foundId);

        $this->assertSame(
            $foundId,
            $this->subject->lookup($labelName, $labelId)
        );
    }

    public function testRemoveArtistAssocDeletes(): void
    {
        $labelId  = 666;
        $artistId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
                [$labelId, $artistId]
            )
            ->once();

        $this->subject->removeArtistAssoc($labelId, $artistId);
    }

    public function testAddArtistAssocCreates(): void
    {
        $labelId  = 666;
        $artistId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
                Mockery::type('array')
            )
            ->once();

        $this->subject->addArtistAssoc($labelId, $artistId);
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $labelId = 666;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `label` WHERE `id` = ?',
                [$labelId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(666);

        $this->assertTrue(
            $this->subject->delete($labelId)
        );
    }

    public function testGetArtistsReturnsList(): void
    {
        $labelId  = 666;
        $artistId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `artist` FROM `label_asso` WHERE `label` = ?',
                [$labelId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $artistId, false);

        $this->assertSame(
            [$artistId],
            $this->subject->getArtists($labelId)
        );
    }

    public function testUpdateUpdates(): void
    {
        $labelId       = 666;
        $name          = 'some-name';
        $category      = 'some-category';
        $summary       = 'some-summary';
        $address       = 'some-address';
        $email         = 'some-email';
        $website       = 'some-website';
        $country       = 'some-country';
        $musicBrainzId = 'some-musicbrainz-id';
        $active        = 1;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `label` SET `name` = ?, `category` = ?, `summary` = ?, `address` = ?, `email` = ?, `website` = ?, `country` = ?, `mbid` = ?, `active` = ? WHERE `id` = ?',
                [$name, $category, $summary, $address, $email, $website, $labelId]
            )
            ->once();

        $this->subject->update(
            $labelId,
            $name,
            $category,
            $summary,
            $address,
            $email,
            $website,
            $country,
            $musicBrainzId,
            $active
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `label_asso` SET `artist` = ? WHERE `artist` = ?',
                [$newObjectId, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }
}
