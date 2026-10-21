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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Broadcast\BroadcastActionView;
use Ampache\Gui\Broadcast\BroadcastListenersView;
use Ampache\Gui\Broadcast\BroadcastsDialogView;
use Ampache\Module\Art\Art;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

final readonly class PlayerAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
    ) {}

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'now_playing':
                // Resolve the real internal song a random/democratic stream is
                // playing (the client playlist only holds a placeholder for those).
                // Match by the streaming session keys this client would register
                // under: the interface session (public / -1 users) and the user's
                // streamtoken (authenticated users) — not the user id, which is
                // shared by all -1 public users.
                $data       = ['found' => false];
                $sessionIds = array_values(array_filter(
                    [(string) session_id(), $user->streamtoken ?? ''],
                    static fn(string $sid): bool => $sid !== ''
                ));
                $current = Stream::get_latest_now_playing($sessionIds);
                if ($current !== null) {
                    $className = ObjectTypeToClassNameMapper::map($current['object_type']);
                    /** @var Song|Video $media */
                    $media = new $className($current['object_id']);
                    if (!$media->isNew() && $media instanceof Song) {
                        $web_path   = AmpConfig::get_web_path();
                        $artistId   = (int) $media->artist;
                        $albumId    = (int) $media->album;
                        $titleText  = scrub_out((string) $media->get_fullname());
                        $artistText = scrub_out(Artist::get_fullname_by_id($artistId));
                        $albumText  = scrub_out((string) $media->get_album_fullname());

                        // per-song action row (album button + rating/flag placeholder) mirroring the regular
                        // playlist flow in playback/web_player.phtml, which random/democratic streams skip
                        $showAlbum = T_('Show Album');
                        $actions   = ($albumId > 0)
                            ? '<a href="javascript:NavigateTo(\'' . $web_path . '/albums.php?action=show&album=' . $albumId . '\')" title="' . $showAlbum . '">' . Ui::get_material_symbol('album', $showAlbum) . '</a> | '
                            : '';
                        $actions .= "<div id='action_buttons_" . $current['object_id'] . '_' . $current['object_type'] . "'></div>";

                        $data = [
                            'found' => true,
                            'object_type' => $current['object_type'],
                            'object_id' => $current['object_id'],
                            'insertion' => $current['insertion'],
                            'duration' => $media->time,
                            'title' => '<a href="javascript:NavigateTo(\'' . $web_path . '/song.php?action=show_song&song_id=' . $media->id . '\')" title="' . $titleText . '">' . $titleText . '</a>',
                            'artist' => ($artistId > 0)
                                ? '<a href="javascript:NavigateTo(\'' . $web_path . '/artists.php?action=show&artist=' . $artistId . '\')">' . $artistText . '</a>'
                                : $artistText,
                            'album' => ($albumId > 0)
                                ? '<a href="javascript:NavigateTo(\'' . $web_path . '/albums.php?action=show&album=' . $albumId . '\')">' . $albumText . '</a>'
                                : $albumText,
                            'art' => Art::url($albumId, 'album') ?? '',
                            'actions' => $actions,
                            'lyrics' => (AmpConfig::get_bool('show_lyrics'))
                                ? '<a href="javascript:NavigateTo(\'' . $web_path . '/song.php?action=show_lyrics&song_id=' . $media->id . '\')">' . T_('Show Lyrics') . '</a>'
                                : '',
                        ];
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($data);

                return;
            case 'scrobble':
                if (check_http_referer() === false) {
                    return;
                }

                // the web player calls this once a song passes the skip timer; only this client's latest unscrobbled play is sent
                $scrobbled = false;
                $current   = ($user->getId() > 0)
                    ? Stream::get_latest_now_playing([(string) session_id(), $user->streamtoken ?? ''])
                    : null;
                $lastPlay = ($current !== null && $current['object_type'] === 'song')
                    ? Stats::get_last_play($user->getId())
                    : null;
                if (
                    $current !== null
                    && $lastPlay !== null
                    && $lastPlay['object_type'] === 'song'
                    && $lastPlay['count_type'] === 'stream'
                    && (int) $lastPlay['object_id'] === $current['object_id']
                    && ($_SESSION['webplayer_scrobble'] ?? 0) !== (int) $lastPlay['id']
                ) {
                    $song = new Song($current['object_id']);
                    if (!$song->isNew()) {
                        $_SESSION['webplayer_scrobble'] = (int) $lastPlay['id'];
                        User::save_mediaplay($user, $song, (int) $lastPlay['date']);
                        $scrobbled = true;
                    }
                }

                header('Content-Type: application/json');
                echo json_encode(['scrobbled' => $scrobbled]);

                return;
            case 'pause':
            case 'play':
            case 'stop':
                if (check_http_referer() === false) {
                    return;
                }

                // trusted the same way PlayerMethod trusts filter/type: only ever touches the caller's own state
                $objectId = (int) $this->requestParser->getFromRequest('oid');
                $type     = strtolower($this->requestParser->getFromRequest('type') ?: 'song');
                $success  = false;

                if ($objectId > 0 && in_array($type, ['song', 'video'], true)) {
                    $className = ObjectTypeToClassNameMapper::map($type);
                    /** @var Song|Video $media */
                    $media = new $className($objectId);

                    if (!$media->isNew()) {
                        $sid      = (string) ($user->streamtoken ?: session_id());
                        $time     = time();
                        $position = max(0, (int) $this->requestParser->getFromRequest('time'));

                        if ($action === 'play') {
                            // resuming shifts the last play's recorded history back to when it actually started (now - position)
                            if ($media instanceof Song) {
                                $agent    = Session::agent($sid);
                                $previous = Stats::get_last_play($user->getId(), $agent);
                                if ((int) $previous['object_id'] === $media->id && $previous['object_type'] === $type) {
                                    $time_diff = $time - (int) $previous['date'];
                                    $old_play  = $time_diff > $media->time * 5;
                                    if ($position >= 1 || $old_play) {
                                        Stats::shift_last_play($user->getId(), $agent, (int) $previous['date'], $time - $position);
                                    }
                                }
                            }

                            // resume is a fresh now_playing row, same as a first play: no partial-length math to get wrong
                            Stream::garbage_collection();
                            Stream::insert_now_playing($objectId, $user->getId(), $media->time, $sid, $type, null, null, null, 'playing');
                        } else {
                            // pause and stop are both "not playing": clear now_playing until play resumes
                            Stream::delete_now_playing($sid, $objectId, $type, $user->getId());
                        }

                        $success = true;
                    }
                }

                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);

                return;
            case 'stream_session':
                // the broadcast listener asks for this when it connects, so a
                // plain page view no longer creates a stream session row
                header('Content-Type: application/json');
                echo json_encode(['session' => Stream::get_session()]);

                return;
            case 'show_broadcasts':
                $broadcasts = [];
                foreach (Broadcast::get_broadcasts(Core::get_global('user')?->getId() ?? 0) as $broadcastId) {
                    $broadcasts[] = new Broadcast($broadcastId);
                }

                $results = new BroadcastsDialogView(
                    $this->ajaxUriRetriever->getAjaxUri(),
                    $broadcasts
                )->render();
                header('Content-Type: text/html; charset=' . AmpConfig::get('site_charset', 'UTF-8'));
                header_remove('Content-Disposition');
                echo $results;

                return;
            case 'broadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                if ($broadcast_id === '' || $broadcast_id === '0') {
                    $broadcast_id = Broadcast::create(T_('My Broadcast'));
                }

                $broadcast = new Broadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $key = Core::generate_random_key();
                    $broadcast->update_state(1, $key);
                    $results['broadcast']                = new BroadcastActionView((int) $broadcast_id)->render() . "<script>startBroadcast('" . $key . "');</script>";
                    $results['broadcast_listeners_wrap'] = new BroadcastListenersView()->render();
                }

                break;
            case 'unbroadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                $broadcast    = new Broadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $broadcast->update_state(0);
                    $results['broadcast']                = new BroadcastActionView()->render() . '<script>stopBroadcast();</script>';
                    $results['broadcast_listeners_wrap'] = '';
                }
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
