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

use Ampache\MockeryTestCase;
use Ampache\Repository\ArtistRepositoryInterface;
use DI\Container;
use Override;

/**
 * A listing resolves the same artist for every track it renders, so the name has to be read once per request.
 */
class ArtistNameCacheTest extends MockeryTestCase
{
    private ?object $previousDic = null;

    public function testAMissingArtistIsCachedAsWell(): void
    {
        // a row that no longer exists must not be looked up again for every remaining track
        $repository = $this->mock(ArtistRepositoryInterface::class);
        $repository->shouldReceive('getNameArrayById')
            ->with(999)
            ->once()
            ->andReturnNull();

        $this->setDic($repository);

        $expected = ['id' => '', 'name' => '', 'prefix' => '', 'basename' => ''];

        self::assertSame($expected, Artist::get_name_array_by_id(999));
        self::assertSame($expected, Artist::get_name_array_by_id(999));
    }

    public function testTheNameIsReadOncePerRequest(): void
    {
        $row = [
            'id' => '42',
            'name' => 'Some Artist',
            'prefix' => '',
            'basename' => 'Some Artist',
        ];

        $repository = $this->mock(ArtistRepositoryInterface::class);
        $repository->shouldReceive('getNameArrayById')
            ->with(42)
            ->once()
            ->andReturn($row);

        $this->setDic($repository);

        self::assertSame($row, Artist::get_name_array_by_id(42));
        self::assertSame($row, Artist::get_name_array_by_id(42));
    }

    public function testTheVariousArtistPlaceholderNeverReachesTheRepository(): void
    {
        $repository = $this->mock(ArtistRepositoryInterface::class);
        $repository->shouldNotReceive('getNameArrayById');

        $this->setDic($repository);

        self::assertSame('0', Artist::get_name_array_by_id(0)['id']);
    }

    public function testTwoArtistsAreCachedApart(): void
    {
        $repository = $this->mock(ArtistRepositoryInterface::class);
        $repository->shouldReceive('getNameArrayById')
            ->with(42)
            ->once()
            ->andReturn(['id' => '42', 'name' => 'First', 'prefix' => '', 'basename' => 'First']);
        $repository->shouldReceive('getNameArrayById')
            ->with(43)
            ->once()
            ->andReturn(['id' => '43', 'name' => 'Second', 'prefix' => '', 'basename' => 'Second']);

        $this->setDic($repository);

        self::assertSame('First', Artist::get_name_array_by_id(42)['name']);
        self::assertSame('Second', Artist::get_name_array_by_id(43)['name']);
        self::assertSame('First', Artist::get_name_array_by_id(42)['name']);
    }

    #[Override]
    protected function setUp(): void
    {
        global $dic;

        $this->previousDic = $dic;

        Artist::clear_cache();
    }

    #[Override]
    protected function tearDown(): void
    {
        global $dic;

        $dic = $this->previousDic;

        Artist::clear_cache();

        parent::tearDown();
    }

    private function setDic(object $repository): void
    {
        global $dic;

        $container = $this->mock(Container::class);
        $container->shouldReceive('get')
            ->with(ArtistRepositoryInterface::class)
            ->andReturn($repository);

        $dic = $container;
    }
}
