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

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;

class PlayHistorySubqueryTest extends MockeryTestCase
{
    public function testCountWithConsolidationOffMatchesLegacySingleTableQuery(): void
    {
        $this->off();
        // must be byte-identical to the pre-consolidation subquery so behaviour is unchanged
        $expected = "(SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = 5 GROUP BY `object_id`, `object_type`, `user`)";
        $this->assertSame($expected, PlayHistorySubquery::count('song', ['stream'], 5));
    }

    public function testCountWithConsolidationOnMergesSummary(): void
    {
        $this->on();
        $sql = PlayHistorySubquery::count('song', ['stream'], 5);
        $this->assertStringContainsString('UNION ALL', $sql);
        $this->assertStringContainsString("FROM `object_count_summary` WHERE `object_count_summary`.`object_type` = 'song'", $sql);
        $this->assertStringContainsString('SUM(`total`) AS `total`', $sql);
        $this->assertStringContainsString('`object_count_summary`.`user` = 5', $sql);
    }

    public function testExistsOffIsLegacyGlobalQuery(): void
    {
        $this->off();
        $expected = "(SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_id`, `object_type`, `user`)";
        $this->assertSame($expected, PlayHistorySubquery::exists('album', ['stream'], null));
    }

    public function testExistsWithoutUserOmitsUserClause(): void
    {
        $this->on();
        $sql = PlayHistorySubquery::exists('album', ['stream'], null);
        $this->assertStringNotContainsString('`user` =', $sql);
        $this->assertStringContainsString('FROM `object_count_summary`', $sql);
    }

    public function testLastDateOnUsesSummaryDateTo(): void
    {
        $this->on();
        $sql = PlayHistorySubquery::lastDate('song', ['stream', 'skip'], 7);
        $this->assertStringContainsString('MAX(`date_to`) AS `date`', $sql);
        $this->assertStringContainsString("`count_type` IN ('stream', 'skip')", $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
    }

    public function testMultipleCountTypesRenderInList(): void
    {
        $this->off();
        $sql = PlayHistorySubquery::count('song', ['stream', 'skip'], 1);
        $this->assertStringContainsString("`object_count`.`count_type` IN ('stream', 'skip')", $sql);
    }

    private function off(): void
    {
        AmpConfig::set('stats_consolidate_threshold', 0, true);
    }

    private function on(): void
    {
        AmpConfig::set('stats_consolidate_threshold', 30, true);
    }
}
