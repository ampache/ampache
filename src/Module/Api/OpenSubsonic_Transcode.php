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
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;

/**
 * OpenSubsonic_Transcode Class
 *
 * The decision half of the OpenSubsonic `transcoding` extension: given a media item and the capabilities a client
 * declared, work out whether it can play the file as-is, and what it would be handed instead if it cannot.
 *
 * The decision is derived from the same Stream settings that serve the bytes, so `getTranscodeDecision` cannot
 * promise a format `getTranscodeStream` would not actually produce.
 *
 * https://opensubsonic.netlify.app/docs/extensions/transcoding/
 */
final class OpenSubsonic_Transcode
{
    /**
     * Marks a token as coming from this server and this format, so a payload from an older shape is rejected on
     * sight rather than being fed to the transcoder.
     */
    private const string TOKEN_VERSION = 'ts1';

    /**
     * decide
     *
     * Build the TranscodeDecision for a media item against a client's declared capabilities.
     *
     * https://opensubsonic.netlify.app/docs/responses/transcodedecision/
     * @param array<string, mixed> $clientInfo
     * @return array{
     *     'canDirectPlay': bool,
     *     'canTranscode': bool,
     *     'transcodeReason'?: string[],
     *     'errorReason'?: string,
     *     'transcodeParams'?: string,
     *     'sourceStream'?: array<string, mixed>,
     *     'transcodeStream'?: array<string, mixed>
     * }
     */
    public static function decide(Song|Podcast_Episode $media, array $clientInfo): array
    {
        $media_type   = ($media instanceof Song) ? 'song' : 'podcast_episode';
        $source       = (string) $media->type;
        $sourceStream = self::sourceStream($media);

        $reasons = self::directPlayReasons($media, $clientInfo);

        // A server set to always transcode overrides whatever the client says it can play.
        if (AmpConfig::get('transcode', 'default') === 'always') {
            $reasons[] = 'The server is configured to always transcode';
        }

        $decision = [
            'canDirectPlay' => ($reasons === []),
            'canTranscode' => false,
            'sourceStream' => $sourceStream,
        ];

        if ($reasons !== []) {
            $decision['transcodeReason'] = array_values(array_unique($reasons));
        }

        if (AmpConfig::get('transcode', 'default') === 'never') {
            $decision['errorReason'] = 'Transcoding is disabled on this server';

            return $decision;
        }

        $target   = self::targetFormat($clientInfo);
        $settings = Stream::get_transcode_settings_for_media($source, $target, 'api', $media_type);
        if (!isset($settings['format'])) {
            $decision['errorReason'] = 'No transcode output is configured for ' . $source;

            return $decision;
        }

        $format  = (string) $settings['format'];
        $bitrate = self::targetBitrate($clientInfo, $format);

        $decision['canTranscode']    = true;
        $decision['transcodeStream'] = [
            'protocol' => 'http',
            'container' => $format,
            'codec' => $format,
            'audioBitrate' => (int) round($bitrate / 1000),
        ];
        $decision['transcodeParams'] = self::encodeParams([
            'format' => $format,
            'bitrate' => $bitrate,
        ]);

        return $decision;
    }

    /**
     * decodeParams
     *
     * Unpack a token produced by self::encodeParams(), returning null unless the signature still matches. Callers
     * must treat null as a rejected request rather than falling back to a default transcode.
     *
     * @return array{'format': string, 'bitrate': int}|null
     */
    public static function decodeParams(string $token): ?array
    {
        if (!str_contains($token, '.')) {
            return null;
        }

        [$payload, $signature] = explode('.', $token, 2);
        if (!hash_equals(self::sign($payload), self::base64UrlDecode($signature))) {
            return null;
        }

        $settings = json_decode(self::base64UrlDecode($payload), true);
        if (
            !is_array($settings)
            || ($settings['v'] ?? null) !== self::TOKEN_VERSION
            || !isset($settings['format'], $settings['bitrate'])
        ) {
            return null;
        }

        return [
            'format' => (string) $settings['format'],
            'bitrate' => (int) $settings['bitrate'],
        ];
    }

    /**
     * encodeParams
     *
     * Pack the resolved transcode settings into the opaque token the spec hands back to clients. It is signed with
     * the server secret because getTranscodeStream feeds these values into the transcoder, so a client-authored
     * token would otherwise choose its own output format and bitrate.
     *
     * @param array{'format': string, 'bitrate': int} $settings
     */
    public static function encodeParams(array $settings): string
    {
        $payload   = self::base64UrlEncode((string) json_encode($settings + ['v' => self::TOKEN_VERSION]));
        $signature = self::base64UrlEncode(self::sign($payload));

        return $payload . '.' . $signature;
    }

    private static function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * directPlayReasons
     *
     * Why the client cannot play the file as it stands, as one human-readable string per unmet constraint. An empty
     * list means direct play is fine. A client that declares no profiles at all is taken at its word and allowed to
     * direct play, matching the spec's reading of an empty capability list as "no restriction".
     *
     * @param array<string, mixed> $clientInfo
     * @return string[]
     */
    private static function directPlayReasons(Song|Podcast_Episode $media, array $clientInfo): array
    {
        $reasons  = [];
        $source   = strtolower((string) $media->type);
        $profiles = (is_array($clientInfo['directPlayProfiles'] ?? null)) ? $clientInfo['directPlayProfiles'] : [];

        if ($profiles !== []) {
            $matched     = false;
            $containerOk = false;
            foreach ($profiles as $profile) {
                if (!is_array($profile)) {
                    continue;
                }

                if (!self::profileCarriesFormat($profile, $source)) {
                    continue;
                }

                // The format is covered, so any remaining mismatch is a limit rather than an unsupported container.
                $containerOk = true;
                if (self::profileAllowsChannels($profile, $media)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $reasons[] = ($containerOk)
                    ? 'The channel count exceeds what the client supports for ' . $source
                    : 'The client does not support the ' . $source . ' container';
            }
        }

        $maxBitrate = (int) ($clientInfo['maxAudioBitrate'] ?? 0);
        if ($maxBitrate > 0 && $media->bitrate > ($maxBitrate * 1000)) {
            $reasons[] = 'The bitrate exceeds the client maximum of ' . $maxBitrate . ' kbps';
        }

        return $reasons;
    }

    /**
     * profileAllowsChannels
     *
     * Whether the media fits within a profile's channel limit. Kept apart from the format check so a rejection can
     * say which constraint was actually missed.
     *
     * @param array<string, mixed> $profile
     */
    private static function profileAllowsChannels(array $profile, Song|Podcast_Episode $media): bool
    {
        $maxChannels = (int) ($profile['maxAudioChannels'] ?? 0);

        return !(
            $maxChannels > 0
            && $media instanceof Song
            && $media->channels !== null
            && $media->channels > $maxChannels
        );
    }

    /**
     * profileCarriesFormat
     *
     * Whether one DirectPlayProfile covers this container and codec. Per the spec an empty list means "any", so only
     * a non-empty list that excludes the value counts as a mismatch.
     *
     * @param array<string, mixed> $profile
     */
    private static function profileCarriesFormat(array $profile, string $source): bool
    {
        foreach (['containers', 'audioCodecs'] as $key) {
            $values = (is_array($profile[$key] ?? null)) ? array_map('strtolower', $profile[$key]) : [];
            if ($values !== [] && !in_array($source, $values, true)) {
                return false;
            }
        }

        return true;
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', self::TOKEN_VERSION . ':' . $payload, (string) AmpConfig::get('secret_key'), true);
    }

    /**
     * sourceStream
     *
     * The StreamDetails of the file as it sits on disk. Ampache stores the container as the file suffix and has no
     * separate codec column, so the two carry the same value; bit depth has no source and is left out.
     *
     * https://opensubsonic.netlify.app/docs/responses/streamdetails/
     * @return array<string, mixed>
     */
    private static function sourceStream(Song|Podcast_Episode $media): array
    {
        $stream = [
            'protocol' => 'http',
            'container' => (string) $media->type,
            'codec' => (string) $media->type,
        ];

        if ($media->bitrate > 0) {
            $stream['audioBitrate'] = (int) round($media->bitrate / 1000);
        }

        if ($media instanceof Song) {
            if ($media->channels !== null && $media->channels > 0) {
                $stream['audioChannels'] = $media->channels;
            }

            if ($media->rate > 0) {
                $stream['audioSamplerate'] = $media->rate;
            }
        }

        return $stream;
    }

    /**
     * targetBitrate
     *
     * The output bitrate in bits per second, held to whatever the server already allows for API players so a client
     * cannot ask its way past the configured ceiling.
     *
     * @param array<string, mixed> $clientInfo
     */
    private static function targetBitrate(array $clientInfo, string $format): int
    {
        $allowed = Stream::get_allowed_bitrate('api');

        $requested = (int) ($clientInfo['maxTranscodingAudioBitrate'] ?? 0);
        if ($requested > 0) {
            $allowed = min($allowed, $requested * 1000);
        }

        $format_max = Stream::get_format_max_bitrate($format);

        return ($format_max > 0)
            ? min($allowed, $format_max)
            : $allowed;
    }

    /**
     * targetFormat
     *
     * The container the client would rather be given. Its transcoding profiles are preferences, not commands, so an
     * unconfigured format is ignored here and Stream falls back to the server's own target.
     *
     * @param array<string, mixed> $clientInfo
     */
    private static function targetFormat(array $clientInfo): ?string
    {
        $profiles = (is_array($clientInfo['transcodingProfiles'] ?? null)) ? $clientInfo['transcodingProfiles'] : [];
        foreach ($profiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }

            $container = strtolower((string) ($profile['container'] ?? ''));
            if ($container !== '' && AmpConfig::get('encode_args_' . $container)) {
                return $container;
            }
        }

        return null;
    }
}
