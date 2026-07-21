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

class StreamTest extends MockeryTestCase
{
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

    #[DataProvider('bitrateProvider')]
    public function testValidateBitrateRoundsToKilobitStepsInBps(int $input, int $expected): void
    {
        $this->assertSame($expected, Stream::validate_bitrate($input));
    }
}
