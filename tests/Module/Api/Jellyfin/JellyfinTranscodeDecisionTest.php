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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Song;

class JellyfinTranscodeDecisionTest extends MockeryTestCase
{
    public function testResolveHonoursAnExplicitContainerOverConfig(): void
    {
        AmpConfig::set('transcode', 'default', true);
        AmpConfig::set('encode_target', 'mp3', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);

        $decision = JellyfinTranscodeDecision::resolve($this->songOf('flac_explicit_case', 900000), 'wav', 0, 0);

        $this->assertTrue($decision->transcode);
        $this->assertSame('wav', $decision->format);
    }

    /**
     * A real client (confirmed with jellyfin_apiclient_python against the dev instance) sends
     * MaxStreamingBitrate on every request by default, usually a value far above any real source bitrate.
     * A server with `encode_target` configured for an unrelated reason (e.g. normalising the web player)
     * must not force-transcode every request just because that cap technically differs from the source.
     */
    public function testResolveStaysDirectPlayOnABareFormatMismatchWithDefaultTranscodeMode(): void
    {
        AmpConfig::set('transcode', 'default', true);
        AmpConfig::set('encode_target', 'mp3', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);

        // no explicit container/codec ask, and the huge cap a real client sends by default is nowhere near the source
        $decision = JellyfinTranscodeDecision::resolve($this->songOf('flac_bare_case', 900000), '', 0, 140_000_000);

        $this->assertFalse($decision->transcode);
    }

    public function testResolveStaysDirectPlayWhenNothingIsConfigured(): void
    {
        AmpConfig::set('transcode', 'default', true);
        AmpConfig::set('encode_target', '', true);
        AmpConfig::set('encode_target_flac', '', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);
        AmpConfig::set('encode_args_flac', '', true);

        $decision = JellyfinTranscodeDecision::resolve($this->songOf('flac', 900000), '', 0, 0);

        $this->assertFalse($decision->transcode);
        $this->assertNull($decision->format);
    }

    public function testResolveStaysDirectPlayWhenTheRequestedCapIsNotBelowSourceRate(): void
    {
        AmpConfig::set('transcode', 'default', true);
        AmpConfig::set('encode_target', '', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);

        // a cap at (not below) the source rate is not a genuine bandwidth saving, so nothing triggers a transcode
        $decision = JellyfinTranscodeDecision::resolve($this->songOf('mp3_resample_case', 320000), '', 0, 320000);

        $this->assertFalse($decision->transcode);
    }

    public function testResolveTranscodesASameFormatDownsample(): void
    {
        AmpConfig::set('transcode', 'default', true);
        AmpConfig::set('encode_target', '', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);
        AmpConfig::set('encode_target_mp3_downsample_case', '', true);
        AmpConfig::set('encode_args_mp3_downsample_case', '-f mp3 pipe:1', true);
        AmpConfig::set('max_bit_rate', 0, true);
        AmpConfig::set('transcode_bitrate', 320000, true);

        // a MaxStreamingBitrate genuinely below the source rate is a real bandwidth saving
        $decision = JellyfinTranscodeDecision::resolve($this->songOf('mp3_downsample_case', 320000), '', 0, 128000);

        $this->assertTrue($decision->transcode);
        $this->assertSame('mp3_downsample_case', $decision->format);
    }

    public function testResolveTranscodesToTheConfiguredDefaultTargetWhenTranscodeModeIsAlways(): void
    {
        AmpConfig::set('transcode', 'always', true);
        AmpConfig::set('encode_target', 'mp3', true);
        AmpConfig::set('encode_target_flac_default_case', '', true);
        AmpConfig::set('encode_player_jellyfin_target', '', true);

        $decision = JellyfinTranscodeDecision::resolve($this->songOf('flac_default_case', 900000), '', 0, 0);

        $this->assertTrue($decision->transcode);
        $this->assertSame('mp3', $decision->format);
    }

    public function testResolveUsesTheJellyfinPlayerOverrideOverTheDefault(): void
    {
        AmpConfig::set('transcode', 'always', true);
        AmpConfig::set('encode_target', 'mp3', true);
        AmpConfig::set('encode_player_jellyfin_target', 'opus', true);

        $decision = JellyfinTranscodeDecision::resolve($this->songOf('flac_player_case', 900000), '', 0, 0);

        $this->assertTrue($decision->transcode);
        $this->assertSame('opus', $decision->format);
    }

    private function songOf(string $type, int $bitrate): Song
    {
        $song          = new Song(0);
        $song->type    = $type;
        $song->bitrate = $bitrate;

        return $song;
    }
}
