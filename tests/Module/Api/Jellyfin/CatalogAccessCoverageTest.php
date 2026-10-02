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

namespace Ampache\Module\Api\Jellyfin;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Item ids are a plain encoding of a row id, so a handler that builds a song or an album from one has to ask
 * the caller's catalog filter itself. Sweeping the directory is what makes a handler added later say so too.
 */
class CatalogAccessCoverageTest extends TestCase
{
    /**
     * `ImageMethod` answers unauthenticated callers by design, so it has no caller to ask about, and
     * `PlaylistItemsMethod` lists what a playlist holds after clearing the caller to read that playlist,
     * so its ids come from the list rather than from the request.
     *
     * @var list<string>
     */
    private const array EXEMPT = ['ImageMethod.php', 'PlaylistItemsMethod.php'];

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function handlerProvider(): array
    {
        $root  = __DIR__ . '/../../../../src/Module/Api/Jellyfin/Method';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        $cases = [];
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || in_array($file->getFilename(), self::EXEMPT, true)) {
                continue;
            }

            $source = (string) file_get_contents((string) $file->getRealPath());
            if (preg_match('/new (Song|Album)\(/', $source) === 1) {
                $cases[] = [$file->getFilename(), $source];
            }
        }

        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('handlerProvider')]
    public function testAHandlerThatBuildsAMediaAsksTheCatalogFilter(string $name, string $source): void
    {
        self::assertStringContainsString(
            'Catalog::has_access(',
            $source,
            $name . ' reaches a song or album by a forgeable id without asking the caller\'s catalog filter'
        );
    }
}
