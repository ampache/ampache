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
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Playback\User_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final class PlaybackStateHandler implements PlaybackStateHandlerInterface
{
    public function __construct(
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * getNowPlaying
     *
     * Returns what is currently being played by all users.
     * https://opensubsonic.netlify.app/docs/endpoints/getnowplaying/
     * @param array<string, mixed> $input
     */
    public function getnowplaying(array $input, User $user): void
    {
        unset($user);
        $data   = Stream::get_now_playing();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addNowPlaying($response, $data);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addNowPlaying($response, $data);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueue
     *
     * Returns the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getplayqueue/
     * @param array<string, mixed> $input
     */
    public function getplayqueue(array $input, User $user): void
    {
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPlayQueue($response, $playQueue, (string) $user->username);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPlayQueue($response, $playQueue, (string) $user->username);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueueByIndex
     *
     * Returns the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getplayqueue/
     * @param array<string, mixed> $input
     */
    public function getplayqueuebyindex(array $input, User $user): void
    {
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPlayQueueByIndex($response, $playQueue, (string) $user->username);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPlayQueueByIndex($response, $playQueue, (string) $user->username);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * jukeboxControl
     *
     * Controls the jukebox, i.e., playback directly on the server’s audio hardware.
     * https://opensubsonic.netlify.app/docs/endpoints/jukeboxcontrol/
     * @param array<string, mixed> $input
     */
    public function jukeboxcontrol(array $input, User $user): void
    {
        $action = $this->responseHandler->checkParameter($input, 'action', __FUNCTION__);
        if ($action === false) {
            return;
        }

        // driving the server's own playback is gated like the native localplay method, nothing checked it here
        if (
            !AmpConfig::get('allow_localplay_playback')
            || $user->access < (int) (AmpConfig::get('localplay_level') ?? AccessLevelEnum::ADMIN->value)
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $object_id  = $input['id'] ?? [];
        $controller = AmpConfig::get('localplay_controller', '');
        $localplay  = ($controller) ? new LocalPlay($controller) : null;
        $return     = false;
        if (empty($controller) || empty($localplay) || empty($localplay->type) || !$localplay->connect()) {
            debug_event(self::class, 'Error Localplay controller: ' . (empty($controller) ? 'Is not set' : $controller), 3);
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        debug_event(self::class, 'Using Localplay controller: ' . $controller, 5);
        switch ($action) {
            case 'get':
            case 'status':
                $return = true;
                break;
            case 'start':
                $return = $localplay->play();
                break;
            case 'stop':
                $return = $localplay->stop();
                break;
            case 'skip':
                if (isset($input['index'])) {
                    if ($localplay->skip((int) $input['index'])) {
                        $return = $localplay->play();
                    }
                } elseif (isset($input['offset'])) {
                    debug_event(self::class, 'Skip with offset is not supported on JukeboxControl.', 5);
                } else {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

                    return;
                }
                break;
            case 'set':
                $localplay->delete_all();
                // Intentional break fall-through
            case 'add':
                if ($object_id) {
                    if (!is_array($object_id)) {
                        $rid       = [];
                        $rid[]     = $object_id;
                        $object_id = $rid;
                    }

                    foreach ($object_id as $sub_id) {
                        $song_id = OpenSubsonic_Api::getAmpacheId($sub_id);
                        if (!$song_id) {
                            continue;
                        }

                        $url = null;
                        if (OpenSubsonic_Api::getAmpacheType($sub_id) === 'song') {
                            $media = new Song($song_id);
                            $url   = ($media->isNew() === false)
                                ? $media->play_url('&client=' . $localplay->type, 'api', function_exists('curl_version'), $user->id, $user->streamtoken)
                                : null;
                        }

                        if ($url !== null) {
                            debug_event(self::class, 'Adding ' . $url, 5);
                            $stream        = [];
                            $stream['url'] = $url;
                            $return        = $localplay->add_url(new Stream_Url($stream));
                        }
                    }
                }
                break;
            case 'clear':
                $return = $localplay->delete_all();
                break;
            case 'remove':
                if (isset($input['index'])) {
                    $return = $localplay->delete_track((int) $input['index']);
                } else {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);
                }
                break;
            case 'shuffle':
                $return = $localplay->random(true);
                break;
            case 'setGain':
                $return = $localplay->volume_set(((float) $input['gain']) * 100);
                break;
        }

        if ($return) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->openSubsonicXmlData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->openSubsonicXmlData->addJukeboxStatus($response, $localplay);
                }
            } else {
                $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->openSubsonicJsonData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->openSubsonicJsonData->addJukeboxStatus($response, $localplay);
                }
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
        }
    }

    /**
     * reportPlayback [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/reportplayback/
     * @param array<string, mixed> $input
     */
    public function reportPlayback(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'mediaId', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $state = strtolower((string) ($input['state'] ?? ''));
        if (!in_array($state, ['starting', 'playing', 'paused', 'stopped'], true)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $media       = OpenSubsonic_Api::getAmpacheObject((string) $sub_id);
        $object_type = OpenSubsonic_Api::getAmpacheType((string) $sub_id);
        if (
            $object_type === ''
            || !$media instanceof Media
            || $media->isNew()
            || !isset($media->id, $media->time)
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        Stream::garbage_collection();
        if ($state === 'stopped') {
            Stream::delete_now_playing((string) $user->username, (int) $media->id, $object_type, $user->id);

            $this->responseHandler->responseOutput($input, __FUNCTION__);

            return;
        }

        // The reported position is stored verbatim: the spec derives `positionMs` from the last report received
        $position_ms = (array_key_exists('positionMs', $input)) ? (int) $input['positionMs'] : null;
        if ($position_ms !== null) {
            // an out-of-range position would pin a stuck now_playing row, so keep it within the track
            $position_ms = max(0, min($position_ms, (int) $media->time * 1000));
        }
        $position    = (int) round(($position_ms ?? 0) / 1000);
        $started     = time() - $position;
        $rate        = (array_key_exists('playbackRate', $input)) ? (float) $input['playbackRate'] : null;
        Stream::insert_now_playing((int) $media->id, $user->id, $media->time, (string) $user->username, $object_type, $started, $position_ms, $rate, $state);

        // ignoreScrobble asks for display state only, so the play count and scrobble plugins are left alone.
        $ignoreScrobble = (
            array_key_exists('ignoreScrobble', $input)
            && (strtolower((string) $input['ignoreScrobble']) === 'true' || $input['ignoreScrobble'] === '1')
        );
        if (!$ignoreScrobble && $state === 'playing') {
            $previous = Stats::get_last_play($user->id, $client, $started);
            if (((int) ($previous['object_id'] ?? 0)) !== (int) $media->id && ($started - (int) $previous['date']) > 5) {
                $media->set_played($user->id, $client, [], $started);
            }
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * savePlayQueue [OS]
     *
     * Saves the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/saveplayqueue/
     * @param array<string, mixed> $input
     */
    public function saveplayqueue(array $input, User $user): void
    {
        $id_list  = $input['id'] ?? '';
        $current  = (string) ($input['current'] ?? '');
        $position = (array_key_exists('position', $input))
            ? (int) (((int) $input['position']) / 1000)
            : 0;
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $user_id   = $user->id;
        $time      = time();
        $playQueue = new User_Playlist($user_id, $client);
        if (empty($id_list)) {
            $playQueue->clear();
        } else {
            $media = (!empty($current))
                ? OpenSubsonic_Api::getAmpacheObject($current)
                : null;
            if (
                $media instanceof Media
                && $media->isNew() === false
                && isset($media->time)
            ) {
                // a client can send an out-of-range resume position; keep the now_playing row garbage-collectable
                $position       = max(0, min($position, (int) $media->time));
                $playqueue_time = (int) User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
                // wait a few seconds before smashing out play times
                if ($playqueue_time < ($time - 2)) {
                    $previous = Stats::get_last_play($user_id, $client);
                    $type     = OpenSubsonic_Api::getAmpacheType($current);
                    // long pauses might cause your now_playing to hide
                    Stream::garbage_collection();
                    Stream::insert_now_playing($media->getId(), $user_id, ($media->time - $position), (string) $user->username, $type, ($time - $position));

                    if ($previous['object_id'] && $previous['object_id'] == $media->getId()) {
                        $time_diff = $time - $previous['date'];
                        $old_play  = $time_diff > $media->time * 5;
                        // shift the start time if it's an old play or has been pause/played
                        if ($position >= 1 || $old_play) {
                            Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                        }
                        // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                        if (($position < 1 && $time_diff > 5) && !$old_play) {
                            $media->set_played($user_id, $client, [], $time);
                        }
                    }
                }
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $sub_ids = (is_array($id_list))
                ? $id_list
                : [$id_list];
            $playlist = $this->getAmpacheIdArrays($sub_ids);

            // clear the old list
            $playQueue->clear();
            // set the new items
            $playQueue->add_items($playlist, $time);

            if (
                isset($type)
                && isset($media->id)
            ) {
                $playQueue->set_current_object($type, $media->id, $position);
            }

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($user_id, 'playqueue_time', $time);
            User::set_user_data($user_id, 'playqueue_client', $client);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * savePlayQueueByIndex [OS]
     *
     * Saves the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/saveplayqueuebyindex/
     * @param array<string, mixed> $input
     */
    public function saveplayqueuebyindex(array $input, User $user): void
    {
        $id_list = $input['id'] ?? '';
        $sub_ids = (is_array($id_list))
            ? $id_list
            : [$id_list];
        $index = (int) ($input['currentIndex'] ?? 0);
        if ($index < 0 || $index >= count($sub_ids)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $current  = $sub_ids[$index];
        $position = (array_key_exists('position', $input))
            ? (int) (((int) $input['position']) / 1000)
            : 0;
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $user_id   = $user->id;
        $time      = time();
        $playQueue = new User_Playlist($user_id, $client);
        if (empty($id_list)) {
            $playQueue->clear();
        } else {
            $media = (!empty($current))
                ? OpenSubsonic_Api::getAmpacheObject($current)
                : null;
            if (
                $media instanceof Media
                && $media->isNew() === false
                && isset($media->time)
            ) {
                // a client can send an out-of-range resume position; keep the now_playing row garbage-collectable
                $position       = max(0, min($position, (int) $media->time));
                $playqueue_time = (int) User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
                // wait a few seconds before smashing out play times
                if ($playqueue_time < ($time - 2)) {
                    $previous = Stats::get_last_play($user_id, $client);
                    $type     = OpenSubsonic_Api::getAmpacheType($current);
                    // long pauses might cause your now_playing to hide
                    Stream::garbage_collection();
                    Stream::insert_now_playing($media->getId(), $user_id, ($media->time - $position), (string) $user->username, $type, ($time - $position));

                    if ($previous['object_id'] && $previous['object_id'] == $media->getId()) {
                        $time_diff = $time - $previous['date'];
                        $old_play  = $time_diff > $media->time * 5;
                        // shift the start time if it's an old play or has been pause/played
                        if ($position >= 1 || $old_play) {
                            Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                        }
                        // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                        if (($position < 1 && $time_diff > 5) && !$old_play) {
                            $media->set_played($user_id, $client, [], $time);
                        }
                    }
                }
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $playlist = $this->getAmpacheIdArrays($sub_ids);

            // clear the old list
            $playQueue->clear();
            // set the new items
            $playQueue->add_items($playlist, $time);

            if (
                isset($type)
                && isset($media->id)
            ) {
                $playQueue->set_current_object($type, $media->id, $position);
            }

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($user_id, 'playqueue_time', $time);
            User::set_user_data($user_id, 'playqueue_client', $client);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * scrobble
     *
     * Registers the local playback of one or more media files.
     * https://opensubsonic.netlify.app/docs/endpoints/scrobble/
     * @param array<string, mixed> $input
     */
    public function scrobble(array $input, User $user): void
    {
        $sub_ids = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_ids === false) {
            return;
        }

        if (!is_array($sub_ids)) {
            $rid     = [];
            $rid[]   = $sub_ids;
            $sub_ids = $rid;
        }

        // check that all the objects are valid
        $valid_media = [];
        foreach ($sub_ids as $sub_id) {
            $object      = OpenSubsonic_Api::getAmpacheObject((string) $sub_id);
            $object_type = OpenSubsonic_Api::getAmpacheType($sub_id);
            if ($object_type === "" || !$object instanceof Media || $object->isNew() || !isset($object->time) || !isset($object->id)) {
                continue;
            }

            $valid_media[] = [$object, $object_type];
        }

        if ($valid_media === []) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $submission = (array_key_exists('submission', $input) && (strtolower($input['submission']) === 'true' || $input['submission'] === '1'));
        $client     = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        $playqueue_time = (int) User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
        $now_time       = time();
        // don't scrobble after setting the play queue too quickly
        if ($playqueue_time < ($now_time - 2)) {
            // long pauses might cause your now_playing to hide, and the sweep is the same for every id
            Stream::garbage_collection();
            foreach ($valid_media as list($media, $type)) {
                $time = (isset($input['time']))
                    ? (int) (((int) $input['time']) / 1000)
                    : time();
                $previous  = Stats::get_last_play($user->id, $client, $time);
                $prev_obj  = $previous['object_id'] ?: 0;
                $prev_date = $previous['date'];

                Stream::insert_now_playing((int) $media->id, $user->id, $media->time, (string) $user->username, $type, $time);
                // submission is true: stream finished. Record the play locally
                // (set_played is dedup-guarded) and notify scrobble plugins.
                if ($submission && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    debug_event(self::class, $user->username . ' scrobbled: {' . $media->id . '} at ' . $time, 5);
                    if ($media->set_played($user->id, $client, [], $time) && get_class($media) == Song::class) {
                        User::save_mediaplay($user, $media);
                    }
                }
                // Submission is false and not a repeat. let repeats go through to saveplayqueue
                if ((!$submission) && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    $media->set_played($user->id, $client, [], $time);
                }
            }
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * @param string[] $sub_ids
     * @return array<int, array{
     *     object_id: int,
     *     object_type: string,
     *     track: int
     * }>
     */
    private function getAmpacheIdArrays(array $sub_ids): array
    {
        $ampidarrays = [];
        $track       = 1;
        foreach ($sub_ids as $sub_id) {
            $ampacheId   = OpenSubsonic_Api::getAmpacheId($sub_id);
            $ampacheType = OpenSubsonic_Api::getAmpacheType($sub_id);
            if ($ampacheId) {
                $ampidarrays[] = [
                    'object_id' => $ampacheId,
                    'object_type' => $ampacheType,
                    'track' => $track
                ];
                $track++;
            }
        }

        return $ampidarrays;
    }
}
