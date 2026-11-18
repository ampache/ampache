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

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    protected function tearDown(): void
    {
        AmpConfig::set(ConfigurationKeyEnum::TRUSTED_PROXIES, '', true);
    }

    public function testResolveClientIpIgnoresForwardedHeaderFromUntrustedPeer(): void
    {
        $this->assertSame(
            '203.0.113.9',
            Core::resolveClientIp('203.0.113.9', '198.51.100.7')
        );
    }

    public function testResolveClientIpUsesForwardedHeaderFromTrustedProxy(): void
    {
        AmpConfig::set(ConfigurationKeyEnum::TRUSTED_PROXIES, '203.0.113.9', true);

        $this->assertSame(
            '198.51.100.7',
            Core::resolveClientIp('203.0.113.9', '198.51.100.7')
        );
    }

    public function testResolveClientIpMatchesTrustedProxyByCidr(): void
    {
        AmpConfig::set(ConfigurationKeyEnum::TRUSTED_PROXIES, '203.0.113.0/24', true);

        $this->assertSame(
            '198.51.100.7',
            Core::resolveClientIp('203.0.113.9', '198.51.100.7')
        );
    }

    public function testResolveClientIpSkipsTrustedHopsInForwardedChain(): void
    {
        AmpConfig::set(ConfigurationKeyEnum::TRUSTED_PROXIES, '203.0.113.9,198.51.100.7', true);

        $this->assertSame(
            '198.51.100.42',
            Core::resolveClientIp('203.0.113.9', '198.51.100.42, 198.51.100.7')
        );
    }

    public function testResolveClientIpFallsBackToRemoteAddrWithNoForwardedHeader(): void
    {
        $this->assertSame(
            '203.0.113.9',
            Core::resolveClientIp('203.0.113.9', null)
        );
    }
}
