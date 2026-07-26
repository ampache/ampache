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
use PHPUnit\Framework\Attributes\DataProvider;

class SearchTest extends MockeryTestCase
{
    /**
     * every type that Search::set_order_by() can sort by popularity weight
     *
     * @return list<array{string}>
     */
    public static function weightTypeProvider(): array
    {
        return [
            ['album'],
            ['album_disk'],
            ['artist'],
            ['podcast'],
            ['podcast_episode'],
            ['song'],
            ['video'],
        ];
    }

    /**
     * MySQL rejects an ORDER BY column missing from a DISTINCT select list (error 3065), which broke every header
     * bar artist search once weight sorting was added
     */
    #[DataProvider('weightTypeProvider')]
    public function testWeightSortedSearchSelectsEveryOrderedColumn(string $type): void
    {
        $sql = Search::prepare(['limit' => 5, 'type' => $type, 'weight' => true])['sql'];

        $this->assertMatchesRegularExpression('/ORDER BY `' . $type . '`\.`weight` DESC/', $sql);

        preg_match('/^SELECT (.*?) FROM /', $sql, $select);
        preg_match('/ ORDER BY (.*?)( LIMIT |$)/', $sql, $order);
        $this->assertNotEmpty($select);
        $this->assertNotEmpty($order);

        // only DISTINCT queries are affected here, a plain select is free to order by any column of a joined table
        if (!str_starts_with($select[1], 'DISTINCT')) {
            return;
        }

        foreach (explode(',', $order[1]) as $column) {
            $column = trim(str_ireplace([' DESC', ' ASC'], '', $column));
            $this->assertStringContainsString($column, $select[1], sprintf('%s is ordered by %s but does not select it', $type, $column));
        }
    }
}
