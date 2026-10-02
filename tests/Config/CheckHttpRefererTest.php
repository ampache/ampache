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

namespace Ampache\Config;

use PHPUnit\Framework\TestCase;

class CheckHttpRefererTest extends TestCase
{
    public function testAcceptsAMatchingNonStandardPort(): void
    {
        self::assertTrue($this->check('https://ampache.example.com:8443', 'https://ampache.example.com:8443/albums.php'));
    }

    public function testAcceptsAnExactOriginMatch(): void
    {
        self::assertTrue($this->check('https://ampache.example.com', 'https://ampache.example.com/albums.php'));
    }

    public function testAllowsAMissingRefererAndWebPathAsTheCliTestsCase(): void
    {
        self::assertTrue($this->check('', null));
    }

    public function testRejectsAMismatchedPort(): void
    {
        self::assertFalse($this->check('https://ampache.example.com', 'https://ampache.example.com:8443/'));
    }

    public function testRejectsAMismatchedScheme(): void
    {
        self::assertFalse($this->check('https://ampache.example.com', 'http://ampache.example.com/'));
    }

    public function testRejectsAMissingRefererWhenWebPathIsConfigured(): void
    {
        self::assertFalse($this->check('https://ampache.example.com', null));
    }

    public function testRejectsAnOriginEmbeddedAsASubdomainPrefix(): void
    {
        self::assertFalse($this->check(
            'https://ampache.example.com',
            'https://ampache.example.com.attacker.example/'
        ));
    }

    public function testRejectsAnOriginEmbeddedInAQueryParameter(): void
    {
        self::assertFalse($this->check(
            'https://ampache.example.com',
            'http://attacker.example/click?next=https://ampache.example.com'
        ));
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        AmpConfig::set('web_path', '', true);
    }

    private function check(string $webPath, ?string $referer): bool
    {
        AmpConfig::set('web_path', $webPath, true);

        if ($referer === null) {
            unset($_SERVER['HTTP_REFERER']);
        } else {
            $_SERVER['HTTP_REFERER'] = $referer;
        }

        return check_http_referer();
    }
}
