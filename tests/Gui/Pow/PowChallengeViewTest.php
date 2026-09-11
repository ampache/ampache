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

    /** What a request looks like to PHP when something in front of it terminated the TLS. */
    private const string TARGET_BEHIND_PROXY = 'http://music.example/batch.php?action=album&id=42';

    private const string WEB_PATH = 'https://music.example';

    /**
     * The return url comes from a client supplied `Referer` and is handed to `location.replace()`.
     *
     * It is reduced to a path and query, so an absolute url cannot come out of it and an open
     * redirect is not expressible. Scheme and port are not compared: behind a proxy that terminates
     * TLS the request reaches PHP as http while the browser sends an https referer.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function refererDataProvider(): array
    {
        return [
            'same origin page' => [self::TARGET, 'https://music.example/albums.php?id=42', '/albums.php?id=42'],
            // The request says http because a proxy terminated the TLS; the visitor is still on https.
            'https referer behind a plain http request' => [self::TARGET_BEHIND_PROXY, 'https://music.example/albums.php?id=42', '/albums.php?id=42'],
            'host in another case' => [self::TARGET, 'https://MUSIC.EXAMPLE/albums.php', '/albums.php'],
            'referer already relative' => [self::TARGET, '/albums.php?id=7', '/albums.php?id=7'],
            'absent' => [self::TARGET, '', self::FALLBACK],
            'another host' => [self::TARGET, 'https://evil.example/landing', self::FALLBACK],
            'host the origin is a prefix of' => [self::TARGET, 'https://music.example.evil.test/landing', self::FALLBACK],
            'protocol relative' => [self::TARGET, '//evil.example/landing', self::FALLBACK],
            'javascript' => [self::TARGET, 'javascript:alert(1)', self::FALLBACK],
            'data' => [self::TARGET, 'data:text/html,<script>alert(1)</script>', self::FALLBACK],
            // Going back to the protected link would just start the download over again.
            'the protected link itself' => [self::TARGET, 'https://music.example/batch.php?action=artist&id=9', self::FALLBACK],
        ];
    }

    public function testConfirmsDeliveryIsOffUnlessTheEndpointCanEchoTheCookie(): void
    {
        $challenge = new PowChallenge(str_repeat('a', 32), 21, 1893456000, str_repeat('b', 64));

        $waits = new PowChallengeView($challenge, self::TARGET, self::WEB_PATH, '', true);
        $timer = new PowChallengeView($challenge, self::TARGET, self::WEB_PATH, '');

        self::assertTrue($waits->confirmsDelivery());
        self::assertFalse($timer->confirmsDelivery());
    }

    #[DataProvider('refererDataProvider')]
    public function testGetReturnUrlOnlyTrustsTheSameOrigin(string $target, string $referer, string $expected): void
    {
        $subject = new PowChallengeView(
            new PowChallenge(str_repeat('a', 32), 21, 1893456000, str_repeat('b', 64)),
            $target,
            self::WEB_PATH,
            $referer
        );

        self::assertSame($expected, $subject->getReturnUrl());
    }

    public function testGetTargetFieldsDropsAStaleAcknowledgementToken(): void
    {
        $subject = new PowChallengeView(
            new PowChallenge(str_repeat('a', 32), 21, 1893456000, str_repeat('b', 64)),
            self::TARGET . '&pow_ack=' . str_repeat('c', 32) . '&pow_nonce=1',
            self::WEB_PATH,
            ''
        );

        $names = array_column($subject->getTargetFields(), 'name');

        // A token carried over from an earlier attempt would satisfy the poll before this delivery
        // has even started, sending the visitor away while the archive is still being written.
        self::assertNotContains('pow_ack', $names);
        self::assertNotContains('pow_nonce', $names);
        self::assertContains('action', $names);
    }
}
