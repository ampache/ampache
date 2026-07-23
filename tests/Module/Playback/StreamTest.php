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

namespace Ampache\Module\Playback;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

class StreamTest extends MockeryTestCase
{
    /**
     * The current config syntax has no suffix, a pre-8.0.0 one keeps a lower or upper case `k`, and the last row
     * covers `%BITRATE%` not matching inside `%MAXBITRATE%`
     *
     * @return list<array{string, string}>
     */
    public static function bitratePlaceholderProvider(): array
    {
        return [
            ['-vn -b:a %BITRATE% -c:a libmp3lame -f mp3 pipe:1', '-vn -b:a 256000 -c:a libmp3lame -f mp3 pipe:1'],
            ['-vn -b:a %BITRATE%k -c:a libmp3lame -f mp3 pipe:1', '-vn -b:a 256000 -c:a libmp3lame -f mp3 pipe:1'],
            ['-vn -b:a %BITRATE%K -c:a libmp3lame -f mp3 pipe:1', '-vn -b:a 256000 -c:a libmp3lame -f mp3 pipe:1'],
            ['-maxrate %MAXBITRATE%K -preset superfast pipe:1', '-maxrate 8000000 -preset superfast pipe:1'],
            ['-ar %SAMPLE%k pipe:1', '-ar 256000 pipe:1'],
            ['-b:a %BITRATE% -maxrate %MAXBITRATE% pipe:1', '-b:a 256000 -maxrate 8000000 pipe:1'],
        ];
    }

    /**
     * @return list<array{int, int}>
     */
    public static function bitrateProvider(): array
    {
        return [
            [128000, 128000],
            [128500, 128000],
            [320999, 320000],
            [7999, 7000],
            [0, 0],
        ];
    }

    public function testGetAllowedBitrateReturnsUserBitrateInBpsWhenNoDownsampling(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('min_bit_rate', 8000, true);
        AmpConfig::set('transcode_bitrate', 192000, true);

        $this->assertSame(192000, Stream::get_allowed_bitrate());
    }

    public function testGetAllowedBitrateUsesPerFormatOverrideWhenPresent(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('min_bit_rate', 8000, true);
        AmpConfig::set('transcode_bitrate', 192000, true);
        AmpConfig::set('transcode_bitrate_formats', 'mp3=256000,opus=96000', true);

        $this->assertSame(96000, Stream::get_allowed_bitrate('opus'));
        $this->assertSame(256000, Stream::get_allowed_bitrate('mp3'));
        // a format with no override falls back to the user default
        $this->assertSame(192000, Stream::get_allowed_bitrate('ogg'));
        $this->assertSame(192000, Stream::get_allowed_bitrate());
    }

    public function testGetAvailableEncodeFormatsOnlyReturnsConfiguredFormats(): void
    {
        // clear the audio formats, then enable a subset (including a ReplayGain profile)
        foreach (['mp3', 'ogg', 'opus', 'm4a', 'wav', 'mp3_rg', 'mp3_car', 'opus_rg', 'opus_car'] as $format) {
            AmpConfig::set('encode_args_' . $format, '', true);
        }

        AmpConfig::set('encode_args_mp3', '-f mp3 pipe:1', true);
        AmpConfig::set('encode_args_opus_rg', '-f opus pipe:1', true);

        $this->assertSame(['mp3', 'opus_rg'], Stream::get_available_encode_formats('audio'));
    }

    public function testGetAvailableEncodeFormatsSeparatesVideoFromAudio(): void
    {
        foreach (['flv', 'webm', 'ts', 'ogv'] as $format) {
            AmpConfig::set('encode_args_' . $format, '', true);
        }

        AmpConfig::set('encode_args_webm', '-f webm pipe:1', true);

        $this->assertSame(['webm'], Stream::get_available_encode_formats('video'));
    }

    public function testGetFormatBitrateMapIgnoresMalformedEntries(): void
    {
        AmpConfig::set('transcode_bitrate', 128000, true);
        AmpConfig::set('transcode_bitrate_formats', 'mp3=256000,broken,opus=0,=5000, ogg = 160000 ', true);

        $this->assertSame(['mp3' => 256000, 'ogg' => 160000], Stream::get_format_bitrate_map());
    }

    public function testGetFormatBitrateReturnsDefaultForEmptyMap(): void
    {
        AmpConfig::set('transcode_bitrate', 128000, true);
        AmpConfig::set('transcode_bitrate_formats', '', true);

        $this->assertSame([], Stream::get_format_bitrate_map());
        $this->assertSame(128000, Stream::get_format_bitrate('mp3'));
    }

    public function testGetTranscodeFormatHonoursExplicitTargetOverPreferences(): void
    {
        AmpConfig::set('encode_target', 'opus', true);
        AmpConfig::set('encode_player_webplayer_target', 'ogg', true);

        // an explicit format request wins over every configured preference
        $this->assertSame('mp3', Stream::get_transcode_format('flac', 'mp3', 'webplayer', 'song'));
    }

    public function testGetTranscodeFormatUsesPlayerTargetOverDefault(): void
    {
        AmpConfig::set('encode_target', 'ogg', true);
        AmpConfig::set('encode_player_api_target', 'opus', true);

        // with no explicit request, the per-player override beats the default target
        $this->assertSame('opus', Stream::get_transcode_format('wav', null, 'api', 'song'));
    }

    /**
     * an untouched `%BITRATE%K` from an old config sent ffmpeg `-b:a 256000K` (256 gigabit) and killed the transcode
     */
    #[DataProvider('bitratePlaceholderProvider')]
    public function testReplaceBitratesConsumesLegacyKilobitSuffix(string $command, string $expected): void
    {
        $method = new ReflectionMethod(Stream::class, '_replace_bitrates');

        $this->assertSame(
            $expected,
            $method->invoke(null, $command, ['%SAMPLE%' => 256000, '%BITRATE%' => 256000, '%MAXBITRATE%' => 8000000])
        );
    }

    public function testSkipTranscodeIgnoresADifferentOutputFormat(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 320000, true);

        // converting flac to mp3 is the point of the transcode, so the rates on either side of it never matter
        $this->assertFalse(Stream::skip_transcode('mp3', 'flac', 128000));
    }

    public function testSkipTranscodeKeepsARealDownsample(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 128000, true);

        // the configured rate, an explicit request and a maxbitrate all save bandwidth when they land under the source
        $this->assertFalse(Stream::skip_transcode('mp3', 'mp3', 320000));
        $this->assertFalse(Stream::skip_transcode('mp3', 'mp3', 320000, 192000));
        $this->assertFalse(Stream::skip_transcode('mp3', 'mp3', 320000, 320000, 256000));
    }

    public function testSkipTranscodeNeverSkipsOnAnUnknownSourceBitrate(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 128000, true);

        $this->assertFalse(Stream::skip_transcode('mp3', 'mp3', 0));
    }

    public function testSkipTranscodeSkipsASameFormatUpsample(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 256000, true);

        // the configured 256000, an explicit request at or above the source, and a maxbitrate over it are all pointless
        $this->assertTrue(Stream::skip_transcode('mp3', 'mp3', 192000));
        $this->assertTrue(Stream::skip_transcode('mp3', 'mp3', 192000, 320000));
        $this->assertTrue(Stream::skip_transcode('mp3', 'mp3', 192000, 192000));
        $this->assertTrue(Stream::skip_transcode('mp3', 'mp3', 192000, 320000, 256000));
    }

    public function testSkipTranscodeUsesThePerFormatBitrateOverride(): void
    {
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 320000, true);
        AmpConfig::set('transcode_bitrate_formats', 'opus=96000', true);

        // opus is capped at 96000 by the override so a 128000 source still downsamples; mp3 has none and uses 320000
        $this->assertFalse(Stream::skip_transcode('opus', 'opus', 128000));
        $this->assertTrue(Stream::skip_transcode('mp3', 'mp3', 128000));
    }

    #[DataProvider('bitrateProvider')]
    public function testValidateBitrateRoundsToKilobitStepsInBps(int $input, int $expected): void
    {
        $this->assertSame($expected, Stream::validate_bitrate($input));
    }
}
