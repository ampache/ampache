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

namespace Ampache\Gui\Pow;

use Ampache\Module\Pow\PowChallenge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PowChallengeViewTest extends TestCase
{
    private const string FALLBACK = 'https://music.example/index.php';

    private const string TARGET = 'https://music.example/batch.php?action=album&id=42';

    private const string WEB_PATH = 'https://music.example';

    /**
     * The return url is taken from a client supplied `Referer` and handed to `location.replace()`, so
     * anything that is not the same origin as the link being replayed has to fall back instead.
     *
     * @return array<string, array{string, string}>
     */
    public static function refererDataProvider(): array
    {
        return [
            'same origin page' => ['https://music.example/albums.php?id=42', 'https://music.example/albums.php?id=42'],
            'absent' => ['', self::FALLBACK],
            'another host' => ['https://evil.example/landing', self::FALLBACK],
            'host the origin is a prefix of' => ['https://music.example.evil.test/landing', self::FALLBACK],
            'another scheme' => ['http://music.example/albums.php', self::FALLBACK],
            'another port' => ['https://music.example:8443/albums.php', self::FALLBACK],
            'protocol relative' => ['//evil.example/landing', self::FALLBACK],
            'javascript' => ['javascript:alert(1)', self::FALLBACK],
            // Going back to the protected link would just start the download over again.
            'the protected link itself' => ['https://music.example/batch.php?action=artist&id=9', self::FALLBACK],
        ];
    }

    #[DataProvider('refererDataProvider')]
    public function testGetReturnUrlOnlyTrustsTheSameOrigin(string $referer, string $expected): void
    {
        $subject = new PowChallengeView(
            new PowChallenge(str_repeat('a', 32), 21, 1893456000, str_repeat('b', 64)),
            self::TARGET,
            self::WEB_PATH,
            $referer
        );

        self::assertSame($expected, $subject->getReturnUrl());
    }
}
