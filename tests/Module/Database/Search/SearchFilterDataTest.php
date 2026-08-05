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

namespace Ampache\Module\Database\Search;

use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * `Search::filter_data()` is the single sanitisation point for search rule input. The `user_numeric` rules
 * (`other_user`, `other_user_album`, `other_user_artist`, `owner`) carry a user id that some search handlers
 * interpolate into SQL without a placeholder, so this must return an int and never a raw string - otherwise the
 * handlers become SQL-injectable.
 */
class SearchFilterDataTest extends TestCase
{
    private Search $subject;

    public function testNumericAndDaysStayInt(): void
    {
        self::assertSame(5, $this->subject->filter_data('5abc', 'numeric', []));
        self::assertSame(7, $this->subject->filter_data('7', 'days', []));
    }

    public function testTextIsNotCast(): void
    {
        self::assertSame('some text', $this->subject->filter_data('some text', 'text', []));
    }

    public function testUserNumericIsCastToInt(): void
    {
        // a raw string would reach the SQL of the other_user handlers verbatim; the cast reduces it to a safe int
        self::assertSame(1, $this->subject->filter_data('1 AND (SELECT ...)', 'user_numeric', []));
        self::assertSame(0, $this->subject->filter_data('(SELECT 1)', 'user_numeric', []));
        self::assertSame(42, $this->subject->filter_data('42', 'user_numeric', []));
    }

    protected function setUp(): void
    {
        // `new User()` reaches Catalog::get_catalogs() through the `global $dic` bridge
        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $globalDic = $this->createMock(ContainerInterface::class);
        $globalDic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            default => $this->createMock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $globalDic;

        // id 0 means no database read for the search; the -1 user is served from hardcoded data, so this is DB-free
        $this->subject = new Search(0, 'song', new User(-1));
    }
}
