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

namespace Ampache\Module\Database\Query;

use Ampache\MockeryTestCase;
use Ampache\Repository\CatalogRepositoryInterface;
use DI\Container;

class FolderQueryTest extends MockeryTestCase
{
    /**
     * `Query::set_filter()` casts a 'catalog' value to `(int)`, so passing the caller's whole catalog list
     * there silently collapsed to `(int) [3, 4]` === 1 and matched nothing (ampache/ampache#4556's native
     * API side). 'user_catalog' is the array-safe filter `SongQuery`/`AlbumQuery` already use for this.
     */
    public function testUserCatalogFilterListsEveryCatalogTheCallerCanSee(): void
    {
        $catalogRepository = $this->mock(CatalogRepositoryInterface::class);
        $catalogRepository->shouldReceive('getIds')->andReturn([3, 4]);

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->with(CatalogRepositoryInterface::class)->andReturn($catalogRepository);
        $GLOBALS['dic'] = $dic;

        $query          = new Query(0, false);
        $query->user_id = 42;

        $sql = (new FolderQuery())->get_sql_filter($query, 'user_catalog', true);

        self::assertSame(' `folder`.`catalog` IN (3,4,0) AND ', $sql);
    }

    public function testUserCatalogIsAnAllowedFilter(): void
    {
        self::assertContains('user_catalog', FolderQuery::FILTERS);
    }
}
