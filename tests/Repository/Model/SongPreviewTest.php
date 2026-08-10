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
 */

namespace Ampache\Repository\Model;

use Ampache\Repository\SongPreviewRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SongPreviewTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $inserted = null;

    /**
     * @return list<array{0: int|string|null, 1: int|string|null, 2: int, 3: int}>
     */
    public static function trackNumberDataProvider(): array
    {
        return [
            // a plain track number is kept as it is
            [1, '5', 1, 5],
            [2, '12', 2, 12],
            // a vinyl side letter names the disk and the remainder is the track
            [1, 'B1', 2, 1],
            [1, 'A3', 1, 3],
            // a side letter with no number after it leaves nothing for the track
            [1, 'B', 2, 0],
            // nothing usable at all
            [0, '', 1, 0],
            [null, null, 0, 0],
        ];
    }

    /**
     * `disk` and `track` are integer columns, so every shape a provider sends has to arrive as an int.
     */
    #[DataProvider('trackNumberDataProvider')]
    public function testInsertNormalisesDiskAndTrack(
        int|string|null $disk,
        int|string|null $track,
        int $expectedDisk,
        int $expectedTrack,
    ): void {
        Song_Preview::insert([
            'disk' => $disk,
            'track' => $track,
            'file' => 'https://example.com/preview.mp3',
        ]);

        static::assertSame($expectedDisk, $this->inserted['disk'] ?? null);
        static::assertSame($expectedTrack, $this->inserted['track'] ?? null);
    }

    protected function setUp(): void
    {
        $repository = $this->createMock(SongPreviewRepositoryInterface::class);
        $repository->method('insert')->willReturnCallback(function (array $data): ?int {
            $this->inserted = $data;

            return 1;
        });

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturn($repository);

        $GLOBALS['dic'] = $dic;
    }
}
