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

use Ampache\Module\Art\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\Preference;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * OpenSubsonic_Fields Class
 *
 * Shared derivation of the OpenSubsonic-only response fields, so the JSON and XML serializers read the same values
 * from the same models instead of each computing their own. Every method returns plain PHP data; turning that into
 * a JSON key or an XML attribute/child element is the calling serializer's job.
 *
 * Fields the Ampache schema has no source for are deliberately absent rather than faked, and each one is listed on
 * the method that would otherwise carry it. They are all optional in the spec, which requires omission over a
 * placeholder value.
 *
 * https://opensubsonic.netlify.app/docs/responses/
 */
final class OpenSubsonic_Fields
{
    /**
     * Per-request bookmark positions, keyed by user id and then song id. See $this->songBookmarkPosition().
     *
     * @var array<int, array<int, int>>
     */
    private static array $bookmarkPositions = [];

    private BookmarkRepositoryInterface $bookmarkRepository;
    private LabelRepositoryInterface $labelRepository;

    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        LabelRepositoryInterface $labelRepository,
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->labelRepository    = $labelRepository;
    }

    /**
     * parseLyrics
     *
     * Turn a stored lyric blob into timed lines, and into word cues when the caller asked for the enhanced layer.
     * Kept free of the Song model so the parsing itself is coverable without a database.
     *
     * @return array{
     *     'synced': bool,
     *     'line': array<int, array{'value': string, 'start'?: int}>,
     *     'cueLine': array<int, array{
     *         'index': int,
     *         'start': int,
     *         'value': string,
     *         'cue': array<int, array{'start': int, 'byteStart': int, 'byteEnd': int}>
     *     }>
     * }
     */
    public static function parseLyrics(string $text, bool $enhanced = false): array
    {
        $text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $text);
        $text = preg_replace('/\\n\\n/i', "\n", (string) $text);
        $text = str_replace("\r", '', (string) $text);

        $lines    = [];
        $cueLines = [];
        $synced   = false;
        foreach (explode("\n", html_entity_decode($text)) as $line) {
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^\[(\d{2}):(\d{2})[.:](\d{2})\]\s*(.*)$/', $line, $matches)) {
                $lines[] = ['value' => $line];
                continue;
            }

            $synced = true;
            $start  = self::lrcTimeToMilliseconds($matches[1], $matches[2], $matches[3]);
            $cues   = self::parseEnhancedCues($matches[4]);

            // The cue offsets are measured against the tag-free text, so that is what the line must carry.
            $value   = ($cues === []) ? trim($matches[4]) : $cues['value'];
            $index   = count($lines);
            $lines[] = ['value' => $value, 'start' => $start];

            if ($enhanced && $cues !== []) {
                $cueLines[] = [
                    'index' => $index,
                    'start' => $start,
                    'value' => $value,
                    'cue' => $cues['cue'],
                ];
            }
        }

        return [
            'synced' => $synced,
            'line' => $lines,
            'cueLine' => $cueLines,
        ];
    }

    private static function lrcTimeToMilliseconds(string $minutes, string $seconds, string $hundredths): int
    {
        return ((int) $minutes * 60 * 1000) + ((int) $seconds * 1000) + ((int) $hundredths * 10);
    }

    /**
     * parseEnhancedCues
     *
     * Split one Enhanced LRC line into its tag-free text and the word cues pointing into it. Returns an empty array
     * when the line carries no `<mm:ss.xx>` tags at all, so a plain LRC line stays a plain line.
     *
     * `end` is deliberately absent from every cue: Enhanced LRC gives start-only timing, and the spec requires the
     * field to be present on all cues of a line or none.
     *
     * @return array{'value': string, 'cue': array<int, array{'start': int, 'byteStart': int, 'byteEnd': int}>}|array{}
     */
    private static function parseEnhancedCues(string $line): array
    {
        $matches = [];
        if (!preg_match_all('/<(\d{2}):(\d{2})[.:](\d{2})>([^<]*)/', $line, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $value = '';
        $cues  = [];
        foreach ($matches as $match) {
            $word = $match[4];
            if (trim($word) === '') {
                $value .= $word;
                continue;
            }

            // byteStart/byteEnd index the finished string, so spacing lands outside the cue it would otherwise shift.
            $lead   = strlen($word) - strlen(ltrim($word));
            $value .= substr($word, 0, $lead);

            $trimmed    = trim($word);
            $byte_start = strlen($value);
            $value .= $trimmed;

            $cues[] = [
                'start' => self::lrcTimeToMilliseconds($match[1], $match[2], $match[3]),
                'byteStart' => $byte_start,
                'byteEnd' => strlen($value),
            ];

            $value .= substr($word, $lead + strlen($trimmed));
        }

        if ($cues === []) {
            return [];
        }

        return ['value' => $value, 'cue' => $cues];
    }

    /**
     * albumDiscTitles
     *
     * The titled discs of an album. Only discs that actually carry a subtitle are returned — the spec makes `title`
     * required, so an untitled disc has nothing to say and is left out entirely.
     *
     * https://opensubsonic.netlify.app/docs/responses/disctitle/
     * @return array<int, array{'disc': int, 'title': string}>
     */
    public function albumDiscTitles(Album $album): array
    {
        $titles = [];
        foreach ($album->getDisks() as $albumDisk) {
            $title = trim((string) $albumDisk->disksubtitle);
            if ($title !== '') {
                $titles[] = [
                    'disc' => $albumDisk->disk,
                    'title' => $title,
                ];
            }
        }

        return $titles;
    }

    /**
     * albumRecordLabels
     *
     * The record labels of an album. `name` is required by the spec, so a blank one is left out.
     *
     * https://opensubsonic.netlify.app/docs/responses/recordlabel/
     *
     * @return array<int, array{'name': string}>
     */
    public function albumRecordLabels(Album $album): array
    {
        $labels = [];
        foreach ($this->labelRepository->getByAlbum($album->getId()) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $labels[] = ['name' => $name];
            }
        }

        return $labels;
    }

    /**
     * albumReleaseTypes
     *
     * The release types of an album. Ampache stores them as one free-text tag, which taggers conventionally join
     * with a slash or a comma when a release carries more than one type.
     *
     * @return string[]
     */
    public function albumReleaseTypes(Album $album): array
    {
        $release_type = trim((string) $album->release_type);
        if ($release_type === '') {
            return [];
        }

        $types = preg_split('~\s*[/,;]\s*~', $release_type) ?: [];

        return array_values(array_filter($types, static fn(string $type): bool => $type !== ''));
    }

    /**
     * allowedUsers
     *
     * The usernames allowed to collaborate on a playlist. Ampache stores collaborators as a comma-separated list of
     * user ids, while the spec asks for names, so each id is resolved and any that no longer exists is dropped.
     *
     * @return string[]
     */
    public function allowedUsers(Playlist $playlist): array
    {
        $collaborate = trim((string) $playlist->collaborate);
        if ($collaborate === '') {
            return [];
        }

        $names = [];
        foreach (explode(',', $collaborate) as $user_id) {
            $username = User::get_username((int) $user_id);
            if ($username !== '') {
                $names[] = $username;
            }
        }

        return $names;
    }

    /**
     * artistImageUrl
     *
     * A directly-fetchable artist image. Art is only linkable without a session when the server publishes it, so a
     * server keeping art behind auth returns null here and the caller omits the field rather than handing clients a
     * URL that answers with a login page.
     */
    public function artistImageUrl(Artist $artist): ?string
    {
        if (!$artist->has_art() || !Art::isPublic()) {
            return null;
        }

        return Art::url($artist->id, 'artist');
    }

    /**
     * itemDate
     *
     * An ItemDate built from a bare year. Ampache only records the year part of a release, so `month`/`day` are
     * never present; a zero or obviously invalid year yields an empty date and the caller omits the field.
     *
     * https://opensubsonic.netlify.app/docs/responses/itemdate/
     * @return array{'year'?: int}
     */
    public function itemDate(?int $year): array
    {
        return ($year !== null && $year > 0)
            ? ['year' => $year]
            : [];
    }

    /**
     * lastPlayed
     *
     * The `played` field: an ISO 8601 instant, or null when never streamed. Server-wide, like `playCount`.
     *
     * https://opensubsonic.netlify.app/docs/responses/child/
     */
    public function lastPlayed(?int $timestamp): ?string
    {
        return ($timestamp !== null && $timestamp > 0)
            ? date("Y-m-d\TH:i:s\Z", $timestamp)
            : null;
    }

    /**
     * songBookmarkPosition
     *
     * The saved playback position of a song for the requesting user, in the seconds the spec counts in. The whole
     * bookmark set is loaded once per request and memoised, because a Child is built for every row of every list
     * response and a per-song lookup would put a query behind each one.
     */
    public function songBookmarkPosition(Song $song): ?int
    {
        $user = Core::get_global('user');
        if (!$user instanceof User || $user->id === 0) {
            return null;
        }

        if (!array_key_exists($user->id, self::$bookmarkPositions)) {
            $positions = [];
            foreach ($this->bookmarkRepository->getByUser($user) as $bookmark_id) {
                $bookmark = new Bookmark($bookmark_id);
                if ($bookmark->object_type === 'song') {
                    $positions[$bookmark->object_id] = $bookmark->position;
                }
            }

            self::$bookmarkPositions[$user->id] = $positions;
        }

        return self::$bookmarkPositions[$user->id][$song->id] ?? null;
    }

    /**
     * songContributors
     *
     * The contributor artists of a song. Ampache models artist relationships as artist/album-artist/composer rather
     * than as free-form role tags, so the composer is the only relationship that maps onto a Contributor role; the
     * performing artists are already carried by `artists`/`albumArtists`. `subRole` has no source and is omitted.
     *
     * https://opensubsonic.netlify.app/docs/responses/contributor/
     * @return array<int, array{'role': string, 'artist': array{'id': string, 'name': string}}>
     */
    public function songContributors(Song $song): array
    {
        $composer = trim((string) $song->composer);
        if ($composer === '') {
            return [];
        }

        // The composer is a free-text tag rather than an artist row, so there is no artist id to hand back with it.
        return [
            [
                'role' => 'composer',
                'artist' => [
                    'id' => '',
                    'name' => $composer,
                ],
            ],
        ];
    }

    /**
     * songIsrc
     *
     * The ISRCs recorded against a song. Ampache keeps them in `song_map`, so a song with none returns an empty
     * list and the caller omits the field.
     *
     * @return string[]
     */
    public function songIsrc(Song $song): array
    {
        return Song::get_song_map_array($song->id, 'isrc');
    }

    /**
     * songReplayGain
     *
     * The replay gain block of a song. `baseGain` carries the R128 track gain, which ffmpeg stores in Q7.8 fixed
     * point, so it is scaled back to plain dB here. The spec requires `replayGain` itself to always be present on
     * a Child, so the caller emits this even when it comes back empty.
     *
     * https://opensubsonic.netlify.app/docs/responses/replaygain/
     * @return array{
     *     'trackGain'?: float,
     *     'albumGain'?: float,
     *     'trackPeak'?: float,
     *     'albumPeak'?: float,
     *     'baseGain'?: float
     * }
     */
    public function songReplayGain(Song $song): array
    {
        // the gain columns live in song_data, which a Song does not load on construction
        $song->fill_ext_info('replaygain_track_gain, replaygain_track_peak, replaygain_album_gain, replaygain_album_peak, r128_track_gain, r128_album_gain');

        $gain = [];
        if ($song->replaygain_track_gain !== null) {
            $gain['trackGain'] = $song->replaygain_track_gain;
        }

        if ($song->replaygain_album_gain !== null) {
            $gain['albumGain'] = $song->replaygain_album_gain;
        }

        // The spec constrains both peaks to be positive, and a negative value in the tag is meaningless, so drop it.
        if ($song->replaygain_track_peak !== null && $song->replaygain_track_peak >= 0) {
            $gain['trackPeak'] = $song->replaygain_track_peak;
        }

        if ($song->replaygain_album_peak !== null && $song->replaygain_album_peak >= 0) {
            $gain['albumPeak'] = $song->replaygain_album_peak;
        }

        if ($song->r128_track_gain !== null) {
            $gain['baseGain'] = round($song->r128_track_gain / 256, 2);
        }

        return $gain;
    }

    /**
     * sortName
     *
     * The sort name of an artist or album. Ampache splits a leading article off into `prefix`, so the stored `name`
     * already is the sort form; it is only worth returning when it actually differs from the displayed full name.
     */
    public function sortName(?string $name, ?string $fullName): ?string
    {
        $name = trim((string) $name);

        return ($name !== '' && $name !== trim((string) $fullName))
            ? $name
            : null;
    }

    /**
     * structuredLyrics
     *
     * The StructuredLyrics entry for a song, or an empty array when it carries no lyrics. Plain LRC line timings
     * (`[mm:ss.xx]`) make the entry synced; Enhanced LRC word timings (`<mm:ss.xx>`) additionally produce cueLine
     * data, which the spec only permits when the caller asked for `enhanced` and the lyrics are synced.
     *
     * `kind` and `agents` are not emitted: Ampache stores one unattributed lyric layer, `main` is already the
     * default when `kind` is omitted, and the spec says agents should not appear without multiple vocal layers.
     *
     * https://opensubsonic.netlify.app/docs/responses/structuredlyrics/
     * @return array{
     *     'displayArtist': string,
     *     'displayTitle': string,
     *     'lang': string,
     *     'synced': bool,
     *     'line': array<int, array{'value': string, 'start'?: int}>,
     *     'cueLine'?: array<int, array{
     *         'index': int,
     *         'start': int,
     *         'value': string,
     *         'cue': array<int, array{'start': int, 'byteStart': int, 'byteEnd': int}>
     *     }>
     * }|array{}
     */
    public function structuredLyrics(Song $song, bool $enhanced = false): array
    {
        $lyrics = $song->get_lyrics();
        if (empty($lyrics) || empty($lyrics['text'])) {
            return [];
        }

        $parsed = self::parseLyrics((string) $lyrics['text'], $enhanced);
        if ($parsed['line'] === []) {
            return [];
        }

        $entry = [
            'displayArtist' => (string) $song->get_parent_fullname(),
            'displayTitle' => (string) $song->title,
            'lang' => 'xxx',
            'synced' => $parsed['synced'],
            'line' => $parsed['line'],
        ];

        if ($parsed['synced'] && $parsed['cueLine'] !== []) {
            $entry['cueLine'] = $parsed['cueLine'];
        }

        return $entry;
    }

    /**
     * userMaxBitRate
     *
     * The transcode ceiling a Subsonic client is actually held to, in the kilobits per second the spec counts in.
     * Ampache stores every bitrate in bits per second, and mirrors Stream::get_player_bitrate('api') here: the API
     * override wins when set, and an override of 0 counts as unset. The preferences are read for the user being
     * described rather than the caller, since getUsers reports on everyone.
     */
    public function userMaxBitRate(User $user): int
    {
        $bitrate = (int) Preference::get_by_user($user->id, 'transcode_bitrate_api');
        if ($bitrate <= 0) {
            $bitrate = (int) Preference::get_by_user($user->id, 'transcode_bitrate');
        }

        return (int) round($bitrate / 1000);
    }
}
