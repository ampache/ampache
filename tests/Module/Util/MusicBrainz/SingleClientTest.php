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

namespace Ampache\Module\Util\MusicBrainz;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The server and its throttle are read in one place, the container factory, and every caller takes the client
 * from there. `MusicBrainz::newMusicBrainz()` builds one that answers to neither: a single call to it anywhere
 * puts that code back on musicbrainz.org, silently, however the instance is configured.
 */
class SingleClientTest extends TestCase
{
    public function testNothingBuildsItsOwnMusicBrainzClient(): void
    {
        $sourcePath = realpath(__DIR__ . '/../../../../src');

        self::assertIsString($sourcePath);

        $offenders = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = (string) $file->getRealPath();
            if (str_contains((string) file_get_contents($path), 'newMusicBrainz(')) {
                $offenders[] = str_replace($sourcePath, '', $path);
            }
        }

        self::assertSame([], $offenders);
    }
}
