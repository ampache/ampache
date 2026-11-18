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

use Ampache\Config\ConfigContainerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * An mp3 carries its license in a TXXX:LICENSE frame, or in WCOP as Picard writes a license url.
 *
 * The inputs mirror what getID3 hands over for such a file: the WCOP url lands in the `copyright` tag, the TXXX
 * frame only in the raw id3v2 block.
 */
class VaInfoLicenseTest extends TestCase
{
    private const string BY = 'https://creativecommons.org/licenses/by/4.0/';

    private const string BY_SA = 'https://creativecommons.org/licenses/by-sa/4.0/';

    public function testATxxxLicenseFrameIsRead(): void
    {
        $parsed = $this->cleanup([], ['LICENSE' => self::BY]);

        $this->assertSame(self::BY, $parsed['license'] ?? null);
    }

    public function testATxxxLicenseFrameWinsOverWcop(): void
    {
        $parsed = $this->cleanup(['copyright' => [self::BY_SA]], ['LICENSE' => self::BY]);

        $this->assertSame(self::BY, $parsed['license'] ?? null);
    }

    public function testAWcopUrlIsReadAsTheLicense(): void
    {
        $parsed = $this->cleanup(['copyright' => [self::BY_SA]], []);

        $this->assertSame(self::BY_SA, $parsed['license'] ?? null);
    }

    /**
     * @param array<string, list<string>> $tags
     * @param array<string, string> $txxx
     * @return array<string, mixed>
     */
    private function cleanup(array $tags, array $txxx): array
    {
        $configContainer = $this->createMock(ConfigContainerInterface::class);
        $configContainer->method('get')->willReturn(false);

        $frames = [];
        foreach ($txxx as $description => $value) {
            $frames[] = ['frame_name' => 'TXXX', 'description' => $description, 'data' => $value, 'encodingid' => 0];
        }

        $vaInfo = new ReflectionClass(VaInfo::class)->newInstanceWithoutConstructor();
        (function () use ($configContainer, $frames, $txxx): void {
            $this->configContainer = $configContainer;
            $this->_raw            = ['id3v2' => ($frames === []) ? [] : ['TXXX' => $frames, 'comments' => ['text' => $txxx]]];
        })->call($vaInfo);

        return new ReflectionMethod(VaInfo::class, '_cleanup_id3v2')->invoke($vaInfo, $tags);
    }
}
