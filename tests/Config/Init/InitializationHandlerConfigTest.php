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

use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

/**
 * A freshly installed config is copied straight from `ampache.cfg.php.dist`, stamped with whatever
 * `config_version` that file declares. `HeaderView::isConfigOutdated()` flags a config as out of date the
 * moment that stamp falls behind the `CONFIG_VERSION` constant, so a bumped constant with a stale dist file
 * makes every brand new install show the "config outdated" banner immediately after installing.
 */
class InitializationHandlerConfigTest extends TestCase
{
    private const string DIST_FILE = __DIR__ . '/../../../config/ampache.cfg.php.dist';

    public function testDistFileConfigVersionMatchesTheConstant(): void
    {
        $contents = (string) file_get_contents(self::DIST_FILE);

        self::assertMatchesRegularExpression(
            '/^config_version\s*=\s*(\d+)$/m',
            $contents,
            'ampache.cfg.php.dist must declare a config_version'
        );

        preg_match('/^config_version\s*=\s*(\d+)$/m', $contents, $matches);

        self::assertSame(
            (new ReflectionClassConstant(InitializationHandlerConfig::class, 'CONFIG_VERSION'))->getValue(),
            $matches[1],
            'config/ampache.cfg.php.dist config_version is out of sync with InitializationHandlerConfig::CONFIG_VERSION'
        );
    }
}
