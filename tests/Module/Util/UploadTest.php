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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function filenameDataProvider(): array
    {
        return [
            'a plain name is untouched' => ['song.mp3', 'song.mp3'],
            'spaces and unicode survive' => ['Will Atkinson - Victims.mp3', 'Will Atkinson - Victims.mp3'],
            'relative traversal' => ['../../../../tmp/pwned.mp3', 'pwned.mp3'],
            'absolute path' => ['/etc/cron.d/pwned.mp3', 'pwned.mp3'],
            'windows separators' => ['..\\..\\windows\\pwned.mp3', 'pwned.mp3'],
            'mixed separators' => ['../foo\\bar/pwned.mp3', 'pwned.mp3'],
            'a bare traversal segment leaves nothing usable' => ['../..', '..'],
            'empty stays empty' => ['', ''],
        ];
    }

    public function testCheckTargetDirAllowsARealSubdirectory(): void
    {
        $catalogDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ampache-upload-test-' . bin2hex(random_bytes(8));
        $subDir     = $catalogDir . DIRECTORY_SEPARATOR . 'sub';
        mkdir($subDir, 0777, true);

        try {
            $_POST['folder'] = 'sub';

            self::assertSame(realpath($subDir), Upload::check_target_dir($catalogDir));
        } finally {
            unset($_POST['folder']);
            rmdir($subDir);
            rmdir($catalogDir);
        }
    }

    /**
     * A sibling directory that merely starts with the catalog path (`/mnt/music-private` vs `/mnt/music`) must be
     * refused; the old `str_contains()` check accepted it because it is not anchored to a real parent directory.
     */
    public function testCheckTargetDirRefusesASiblingDirectoryThatOnlySharesAPrefix(): void
    {
        $base       = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ampache-upload-test-' . bin2hex(random_bytes(8));
        $catalogDir = $base . '-music';
        $siblingDir = $base . '-music-private';
        mkdir($catalogDir, 0777, true);
        mkdir($siblingDir, 0777, true);

        try {
            $_POST['folder'] = '..' . DIRECTORY_SEPARATOR . basename($siblingDir);

            self::assertNull(Upload::check_target_dir($catalogDir));
        } finally {
            unset($_POST['folder']);
            rmdir($catalogDir);
            rmdir($siblingDir);
        }
    }

    /**
     * The name is joined onto the catalog directory, so a name carrying a path would write outside the catalog.
     */
    #[DataProvider('filenameDataProvider')]
    public function testCleanFilenameKeepsOnlyTheName(string $given, string $expected): void
    {
        self::assertSame($expected, Upload::clean_filename($given));
    }

    /**
     * `%FILE%` is a client-chosen filename substituted straight into an admin-configured shell command;
     * unescaped, a filename carrying shell metacharacters runs arbitrary commands as the web server user
     */
    public function testUploadScriptEscapesShellMetacharactersInTheFilename(): void
    {
        $targetdir = sys_get_temp_dir();
        $marker    = $targetdir . DIRECTORY_SEPARATOR . 'ampache-upload-script-test-' . bin2hex(random_bytes(8));
        $payload   = 'song.mp3; touch ' . escapeshellarg($marker) . ' #';

        AmpConfig::set('allow_upload_scripts', true, true);
        AmpConfig::set('upload_script', 'echo %FILE%', true);

        try {
            Upload::upload_script($targetdir, $payload);

            self::assertFileDoesNotExist($marker);
        } finally {
            AmpConfig::set('allow_upload_scripts', false, true);
            AmpConfig::set('upload_script', '', true);
            if (file_exists($marker)) {
                unlink($marker);
            }
        }
    }
}
