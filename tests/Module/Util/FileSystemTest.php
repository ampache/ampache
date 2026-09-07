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

use Ampache\Repository\Model\User;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the upload file browser sandbox boundary.
 *
 * The base is a per-user directory under the upload catalog. A prefix test without a trailing separator let a path
 * escape into a sibling directory that merely shares the base as a string prefix, so `real()` is pinned here.
 */
class FileSystemTest extends TestCase
{
    private string $root;

    public function testAChildOfTheBaseIsInside(): void
    {
        mkdir($this->root . '/base/sub');
        $fs = new FileSystem($this->root . '/base');

        self::assertSame(realpath($this->root . '/base/sub'), $this->real($fs, $this->root . '/base/sub'));
    }

    public function testASiblingSharingThePrefixIsRejected(): void
    {
        // `/upload/alice` must not admit `/upload/alice_evil`
        $fs = new FileSystem($this->root . '/base');

        $this->expectException(Exception::class);
        $this->real($fs, $this->root . '/base_evil');
    }

    public function testCreateRejectsADotOnlyName(): void
    {
        // a name of `..` would otherwise pass the charset check below and reach mkdir()/file_put_contents();
        // asserting the specific message proves it is rejected up front rather than merely failing later when
        // the computed id turns out to be outside base (which is a warning-noisy, not-guaranteed-safe path)
        $fs = new FileSystem($this->root . '/base');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid name');
        $fs->create('/', '..', true);
    }

    public function testRenameRejectsADotOnlyName(): void
    {
        mkdir($this->root . '/base/sub');
        $fs = new FileSystem($this->root . '/base');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid name');
        $fs->rename('sub', '..', new User());
    }

    public function testTheBaseItselfIsInside(): void
    {
        $fs = new FileSystem($this->root . '/base');

        self::assertSame(realpath($this->root . '/base'), $this->real($fs, $this->root . '/base'));
    }

    protected function setUp(): void
    {
        $this->root = (string) tempnam(sys_get_temp_dir(), 'fs');
        unlink($this->root);
        mkdir($this->root);
        mkdir($this->root . '/base');
        mkdir($this->root . '/base_evil');
    }

    protected function tearDown(): void
    {
        foreach (['/base/sub', '/base', '/base_evil', ''] as $dir) {
            @rmdir($this->root . $dir);
        }
    }

    private function real(FileSystem $fs, string $path): string
    {
        $method = new ReflectionMethod(FileSystem::class, 'real');

        /** @var string $result */
        $result = $method->invoke($fs, $path);

        return $result;
    }
}
