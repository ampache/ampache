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

namespace Ampache\Config\Init;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Shared by the tests that lock down which bootstrap constants (`NO_SESSION`, `OUTDATED_DATABASE_OK`, ...)
 * each `public/`/`bin/cli` entry point defines before requiring `Config/Init.php` (`bin/installer` requires
 * `Config/Bootstrap.php` directly instead, so it never reaches the `Init` chain and is excluded on purpose).
 */
trait EntryPointFileScanningTrait
{
    /**
     * @return list<string>
     */
    private function getEntryPointFiles(): array
    {
        $repositoryRoot = realpath(__DIR__ . '/../../..');

        self::assertIsString($repositoryRoot);

        $entryPoints = ['bin/cli'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repositoryRoot . '/public', FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents((string) $file->getRealPath());

            if (!str_contains($contents, 'Config/Init.php')) {
                continue;
            }

            $entryPoints[] = str_replace(
                [$repositoryRoot . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                ['', '/'],
                (string) $file->getRealPath()
            );
        }

        return $entryPoints;
    }

    /**
     * @return list<string>
     */
    private function getEntryPointFilesDefining(string $constant): array
    {
        $repositoryRoot = realpath(__DIR__ . '/../../..');

        self::assertIsString($repositoryRoot);

        $actual = [];
        foreach ($this->getEntryPointFiles() as $relativePath) {
            $contents = (string) file_get_contents($repositoryRoot . '/' . $relativePath);

            if (str_contains($contents, "define('" . $constant . "'")) {
                $actual[] = $relativePath;
            }
        }

        sort($actual);

        return $actual;
    }
}
