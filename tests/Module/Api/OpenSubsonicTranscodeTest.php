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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use PHPUnit\Framework\TestCase;

/**
 * Covers the opaque `transcodeParams` token of the OpenSubsonic `transcoding` extension.
 *
 * getTranscodeStream feeds the decoded values straight into the transcoder, so a token the server did not sign must
 * never decode. Only the token is covered here: OpenSubsonic_Transcode::decide() needs Song/Podcast_Episode models,
 * whose only seam is `Dba::`, so it is verified over HTTP against a running server instead.
 */
class OpenSubsonicTranscodeTest extends TestCase
{
    private const string SECRET = 'abcdefghijklmnoprqstuvwyz0123456';

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedTokenProvider(): array
    {
        return [
            'empty' => [''],
            'no separator' => ['nonsense'],
            'payload only' => ['eyJmb3JtYXQiOiJtcDMifQ.'],
            'signature only' => ['.KjNvU'],
            'not base64' => ['%%%.%%%'],
        ];
    }

    public function testDecodeParamsRejectsAnAlteredPayload(): void
    {
        $token         = OpenSubsonic_Transcode::encodeParams(['format' => 'mp3', 'bitrate' => 128000]);
        [, $signature] = explode('.', $token, 2);

        // Keep the real signature but swap in a payload asking for a different format at a far higher bitrate.
        $payload = rtrim(
            strtr(base64_encode((string) json_encode(['format' => 'wav', 'bitrate' => 9999000, 'v' => 'ts1'])), '+/', '-_'),
            '='
        );

        self::assertNull(OpenSubsonic_Transcode::decodeParams($payload . '.' . $signature));
    }

    public function testDecodeParamsRejectsATokenSignedByAnotherServer(): void
    {
        $token = OpenSubsonic_Transcode::encodeParams(['format' => 'mp3', 'bitrate' => 128000]);

        AmpConfig::set('secret_key', 'a-completely-different-server-key', true);

        self::assertNull(OpenSubsonic_Transcode::decodeParams($token));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedTokenProvider')]
    public function testDecodeParamsRejectsMalformedTokens(string $token): void
    {
        self::assertNull(OpenSubsonic_Transcode::decodeParams($token));
    }

    public function testEncodeParamsRoundTripsTheSettings(): void
    {
        $token = OpenSubsonic_Transcode::encodeParams(['format' => 'mp3', 'bitrate' => 128000]);

        self::assertSame(
            ['format' => 'mp3', 'bitrate' => 128000],
            OpenSubsonic_Transcode::decodeParams($token)
        );
    }

    protected function setUp(): void
    {
        AmpConfig::set('secret_key', self::SECRET, true);
    }
}
