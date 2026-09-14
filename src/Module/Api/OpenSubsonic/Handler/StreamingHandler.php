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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Transcode;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Module\Art\Art;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use CurlHandle;
use WpOrg\Requests\Requests;

final class StreamingHandler implements StreamingHandlerInterface
{
    public function __construct(
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * download
     *
     * Downloads a given media file.
     * https://opensubsonic.netlify.app/docs/endpoints/download/
     * @param array<string, mixed> $input
     */
    public function download(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client) . '&cache=1';

        $this->follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * getAvatar
     *
     * Returns the avatar (personal image) for a user.
     * https://opensubsonic.netlify.app/docs/endpoints/getavatar/
     * @param array<string, mixed> $input
     */
    public function getavatar(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string) $username);
            }

            if ($update_user instanceof User) {
                // Get Session key
                $avatar = $update_user->get_avatar(true);
                if (!empty($avatar['url'])) {
                    $request = Requests::get($avatar['url'], [], Core::requests_options());
                    header("Content-Type: " . $request->headers['Content-Type']);
                    echo $request->body;
                }
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getCaptions
     *
     * Returns captions (subtitles) for a video.
     * https://opensubsonic.netlify.app/docs/endpoints/getcaptions/
     * @param array<string, mixed> $input
     */
    public function getcaptions(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$video instanceof Video || $video->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // Captions are .srt files sitting beside the video file; the one with no language suffix is the default
        $captions = null;
        foreach ($video->get_subtitles() as $subtitle) {
            if ($captions === null || $subtitle['lang_code'] === '__') {
                $captions = $subtitle;
            }
            if ($subtitle['lang_code'] === '__') {
                break;
            }
        }

        $body = ($captions !== null && is_readable($captions['file']))
            ? file_get_contents($captions['file'])
            : false;
        if ($body === false) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // What is stored is always SubRip, so `vtt` is answered by converting it rather than by finding another file
        if (strtolower((string) ($input['format'] ?? 'srt')) === 'vtt') {
            header('Content-Type: text/vtt; charset=UTF-8');
            echo $this->srtToVtt($body);

            return;
        }

        header('Content-Type: application/x-subrip; charset=UTF-8');
        echo $body;
    }

    /**
     * getCoverArt
     *
     * Returns a cover art image.
     * https://opensubsonic.netlify.app/docs/endpoints/getcoverart/
     * @param array<string, mixed> $input
     */
    public function getcoverart(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        // replace additional prefixes
        $sub_id = preg_replace('/^[a-z]+-([a-z]{2}-)/', '$1', $sub_id);

        $object_id   = OpenSubsonic_Api::getAmpacheId($sub_id);
        $object_type = OpenSubsonic_Api::getAmpacheType($sub_id);
        if (
            !$object_id
            || empty($object_type)
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $art = null;
        if (($object_type == 'song')) {
            if (AmpConfig::get('show_song_art', false) && Art::has_db($object_id, 'song')) {
                $art = new Art($object_id, 'song');
            } else {
                // in most cases the song doesn't have a picture, but the album does
                $song = new Song($object_id);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($object_type == 'artist' || $object_type == 'album' || $object_type == 'podcast' || $object_type == 'playlist') {
            $art = new Art($object_id, $object_type);
        } elseif ($object_type == 'search') {
            $playlist  = new Search($object_id, 'song', $user);
            $listitems = $playlist->get_items();
            $item      = (!empty($listitems)) ? $listitems[array_rand($listitems)] : [];
            $art       = (!empty($item)) ? new Art($item['object_id'], $item['object_type']->value) : null;
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        }

        if (!$art || !$art->has_db_info('original', true)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // clients each pick their own pixel count, and every distinct one is stored and kept, so snap
        // onto a size the interface already makes. Larger than anything we make serves the original.
        $size = (isset($input['size']) && is_numeric($input['size']))
            ? (Art::canonical_size((int) $input['size']) ?? 'original')
            : 'original';

        // we have the art so lets show it
        header("Access-Control-Allow-Origin: *");
        if (is_int($size) && AmpConfig::get('resize_images')) {
            $out_size           = [];
            $out_size['width']  = $size;
            $out_size['height'] = $size;
            $thumb              = $art->get_thumb($out_size);
            if (!empty($thumb) && isset($thumb['thumb']) && isset($thumb['thumb_mime'])) {
                header('Content-type: ' . $thumb['thumb_mime']);
                header('Content-Length: ' . strlen((string) $thumb['thumb']));
                echo $thumb['thumb'];

                return;
            }
        }
        $image = $art->get('original', true);
        header('Content-type: ' . $art->raw_mime);
        header('Content-Length: ' . strlen($image));
        echo $image;
    }

    /**
     * getLyrics
     *
     * Searches for and returns lyrics for a given song.
     * https://opensubsonic.netlify.app/docs/endpoints/getlyrics/
     * @param array<string, mixed> $input
     */
    public function getlyrics(array $input, User $user): void
    {
        $artist = (string) ($input['artist'] ?? '');
        $title  = (string) ($input['title'] ?? '');

        if (empty($artist) && empty($title)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $data           = [];
        $data['limit']  = 1;
        $data['offset'] = 0;
        $data['type']   = "song";

        if ($artist) {
            $data['rule_0_input']    = $artist;
            $data['rule_0_operator'] = 4;
            $data['rule_0']          = "artist";
        }
        if ($title) {
            $data['rule_1_input']    = $title;
            $data['rule_1_operator'] = 4;
            $data['rule_1']          = "title";
        }

        $songs = Search::run($data, $user);
        if (count($songs) > 0) {
            $song = new Song($songs[0]);
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addLyrics($response, $artist, $title, $song);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addLyrics($response, $artist, $title, $song);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLyricsBySongId
     *
     * Add support for synchronized lyrics, multiple languages, and retrieval by song ID
     * https://opensubsonic.netlify.app/docs/endpoints/getlyricsbysongid/
     * @param array<string, mixed> $input
     */
    public function getlyricsbysongid(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }
        $song = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$song instanceof Song || $song->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // [OPENSUBSONIC] `songLyrics`: `enhanced` adds the word-level cueLine layer, if the lyrics carry LRC timings.
        $enhanced = (
            array_key_exists('enhanced', $input)
            && (strtolower((string) $input['enhanced']) === 'true' || $input['enhanced'] === '1')
        );

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addLyricsList($response, $song, $enhanced);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addLyricsList($response, $song, $enhanced);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getTranscodeDecision [OS]
     *
     * OpenSubsonic extension `transcoding`. Reports whether a client can play a media item as it stands, and what it
     * would be given instead. POST only, because the client capabilities arrive as a JSON body.
     * https://opensubsonic.netlify.app/docs/endpoints/gettranscodedecision/
     * @param array<string, mixed> $input
     */
    public function getTranscodeDecision(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'mediaId', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $media = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (
            (!($media instanceof Song || $media instanceof Podcast_Episode))
            || $media->isNew()
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // SubsonicApiApplication decodes the JSON body here; an empty one means no declared limits, not a bad request.
        $clientInfo = (is_array($input['_body'] ?? null)) ? $input['_body'] : [];
        $decision   = OpenSubsonic_Transcode::decide($media, $clientInfo);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addTranscodeDecision($response, $decision);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addTranscodeDecision($response, $decision);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getTranscodeStream [OS]
     *
     * OpenSubsonic extension `transcoding`. Streams a media item using the settings getTranscodeDecision resolved,
     * carried in the opaque `transcodeParams` token.
     * https://opensubsonic.netlify.app/docs/endpoints/gettranscodestream/
     * @param array<string, mixed> $input
     */
    public function getTranscodeStream(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'mediaId', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $token = $this->responseHandler->checkParameter($input, 'transcodeParams', __FUNCTION__);
        if ($token === false) {
            return;
        }

        $media = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (
            (!($media instanceof Song || $media instanceof Podcast_Episode))
            || $media->isNew()
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // A token failing its signature is refused outright, or a client could pick its own output by sending rubbish.
        $settings = OpenSubsonic_Transcode::decodeParams((string) $token);
        if ($settings === null) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client)
            . '&transcode_to=' . $settings['format']
            . '&bitrate=' . $settings['bitrate']
            . '&cache=1';

        $offset = (int) ($input['offset'] ?? 0);
        if ($offset > 0) {
            $params .= '&frame=' . $offset;
        }

        $this->follow_stream($media->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * hls
     *
     * Creates an HLS (HTTP Live Streaming) playlist used for streaming video or audio.
     * https://opensubsonic.netlify.app/docs/endpoints/hls/
     * @param array<string, mixed> $input
     */
    public function hls(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $bitRate = $input['bitRate'] ?? false;
        $media   = [];
        $type    = OpenSubsonic_Api::getAmpacheType($sub_id);
        if ($type === 'song') {
            $media['object_type'] = LibraryItemEnum::SONG;
        } elseif ($type === 'video') {
            $media['object_type'] = LibraryItemEnum::VIDEO;
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $media['object_id'] = $object_id;
        $medias             = [];
        $medias[]           = $media;
        $stream             = new Stream_Playlist();
        $additional_params  = '';
        if ($bitRate) {
            // OpenSubsonic bitRate is kbps, convert to bps
            $additional_params .= '&bitrate=' . ($bitRate * 1000);
        }

        $stream->add($medias, $additional_params);

        // vlc won't work if we use application/vnd.apple.mpegurl, but works fine with this. this is
        // also an allowed header by the standard
        header('Content-Type: audio/mpegurl;');
        echo $stream->create_m3u();
    }

    /**
     * stream
     *
     * Streams a given media file.
     * https://opensubsonic.netlify.app/docs/endpoints/stream/
     * @param array<string, mixed> $input
     */
    public function stream(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $maxBitRate    = (int) ($input['maxBitRate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $timeOffset    = $input['timeOffset'] ?? false;
        $contentLength = $input['estimateContentLength'] ?? false; // Force content-length guessing if transcode
        $client        = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        $params = '&client=' . rawurlencode($client);
        if ($contentLength == 'true') {
            $params .= '&content_length=required';
        }
        if ($format && $format != "raw") {
            $params .= '&transcode_to=' . $format;
        }
        if ($maxBitRate > 0) {
            $params .= '&bitrate=' . ($maxBitRate * 1000); // OpenSubsonic maxBitRate is kbps, convert to bps
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        // No scrobble for streams using open subsonic https://opensubsonic.netlify.app/docs/endpoints/stream/
        $params .= '&cache=1';

        $this->follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    private function follow_stream(string $url): void
    {
        set_time_limit(0);
        ob_end_clean();
        header("Access-Control-Allow-Origin: *");
        if (function_exists('curl_version')) {
            // Here, we use curl from the Ampache server to download data from
            // the Ampache server, which can be a bit counter-intuitive.
            // We use the curl `writefunction` and `headerfunction` callbacks
            // to write the fetched data back to the open stream from the
            // client.
            $headers    = apache_request_headers();
            $reqheaders = [];
            if (isset($headers['User-Agent'])) {
                $reqheaders[] = "User-Agent: " . $headers['User-Agent'];
            }
            if (isset($headers['Range'])) {
                $reqheaders[] = "Range: " . $headers['Range'];
            }
            $reqheaders[] = "X-Forwarded-For: " . Core::get_user_ip();
            // Curl support, we stream transparently to avoid redirect. Redirect can fail on few clients
            debug_event(self::class, 'Stream proxy: ' . $url, 5);
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array(
                    $curl,
                    [
                        CURLOPT_FAILONERROR => true,
                        CURLOPT_HTTPHEADER => $reqheaders,
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_WRITEFUNCTION => $this->output_body(...),
                        CURLOPT_HEADERFUNCTION => $this->output_header(...),
                        // Ignore invalid certificate
                        // Default trusted chain is crap anyway and currently no custom CA option
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_TIMEOUT => 0
                    ]
                );
                if (curl_exec($curl) === false) {
                    debug_event(self::class, 'Stream error: ' . curl_error($curl), 1);
                }
            }
        } else {
            // Stream media using http redirect if no curl support
            // Bug fix for android clients looking for /rest/ in destination url
            // Warning: external catalogs will not work!
            $url = str_replace('/play/', '/rest/fake/', $url);
            header("Location: " . $url);
        }
    }

    private function output_body(CurlHandle $curl, string $data): int
    {
        unset($curl);

        echo $data;
        ob_flush();

        return strlen($data);
    }

    private function output_header(CurlHandle $curl, string $header): int
    {
        $rheader = trim($header);
        $rhpart  = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        } elseif (str_starts_with($header, "HTTP/")) {
            // if $header starts with HTTP/ assume it's the status line
            http_response_code(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        }

        return strlen($header);
    }

    /**
     * Convert a SubRip caption body to WebVTT.
     *
     * The two formats differ only in the header line and in the decimal separator of a cue's timestamps, so the
     * cue text itself is passed through untouched.
     */
    private function srtToVtt(string $srt): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $srt);
        $body = (string) preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $body = (string) preg_replace(
            '/(\d{2}:\d{2}:\d{2}),(\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}),(\d{3})/',
            '$1.$2 --> $3.$4',
            $body
        );

        return "WEBVTT\n\n" . ltrim($body, "\n");
    }
}
