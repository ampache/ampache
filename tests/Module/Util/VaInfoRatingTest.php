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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** A rating written to the file came back as 0 or 1 star on the next scan (#4264) */
class VaInfoRatingTest extends TestCase
{
    /**
     * @return list<array{0: bool}>
     */
    public static function compatibilityModes(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('compatibilityModes')]
    public function testAWrittenRatingReadsBackTheSame(bool $compatibility): void
    {
        AmpConfig::set('rating_file_tag_compatibility', $compatibility, true);

        foreach ([1, 2, 3, 4, 5] as $stars) {
            self::assertSame($stars, VaInfo::parse_rating(VaInfo::rating_to_popm($stars)));
        }
    }

    public function testCompatibilityWritesTheValuesOtherPlayersUse(): void
    {
        AmpConfig::set('rating_file_tag_compatibility', true, true);

        self::assertSame([1, 64, 128, 196, 255], array_map(VaInfo::rating_to_popm(...), [1, 2, 3, 4, 5]));
    }

    public function testFiveStarsFillTheScale(): void
    {
        AmpConfig::set('rating_file_tag_compatibility', false, true);

        self::assertSame(255, VaInfo::rating_to_popm(5));
        self::assertSame(0, VaInfo::rating_to_popm(null));
    }

    protected function tearDown(): void
    {
        AmpConfig::set('rating_file_tag_compatibility', false, true);
    }
}
