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
use PHPUnit\Framework\Attributes\DataProvider;

class QueryTest extends MockeryTestCase
{
    public function testMatchModeDefaultsToStartsWith(): void
    {
        $this->assertSame('starts_with', $this->subject()->get_match_mode());
    }

    public function testMatchModeRemembersAnOfferedMode(): void
    {
        $query = $this->subject();
        $query->set_match_mode('like');

        $this->assertSame('like', $query->get_match_mode());
    }

    /**
     * A rejected mode has to leave the previous one alone: the filter box posts whatever arrives in the
     * request, and falling back to the default would silently switch the user's choice back.
     */
    #[DataProvider('refusedModeDataProvider')]
    public function testMatchModeRefusesAnythingElse(string $refused): void
    {
        $query = $this->subject();
        $query->set_match_mode('like');
        $query->set_match_mode($refused);

        $this->assertSame('like', $query->get_match_mode());
    }

    /**
     * @return list<array{0: string}>
     */
    public static function refusedModeDataProvider(): array
    {
        return [
            [''],
            ['not_like'],
            ['alpha_match'],
            ['exact_match'],
            ['1 OR 1=1'],
        ];
    }

    public function testClearFilterIgnoresAFilterThatWasNeverSet(): void
    {
        $query = $this->subject();
        $query->clear_filter('like');

        $this->assertNull($query->get_filter('like'));
    }

    public function testMatchModesAreTheOnesTheFilterBoxOffers(): void
    {
        $this->assertSame(['starts_with', 'like'], Query::MATCH_MODES);
    }

    /**
     * An uncached query keeps its state in memory and never reaches the database.
     */
    private function subject(): Query
    {
        return new Query(0, false);
    }
}
