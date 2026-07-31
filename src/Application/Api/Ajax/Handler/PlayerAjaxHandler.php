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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Art;
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
        private UiInterface $ui,
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
                    [(string) session_id(), (string) ($user->streamtoken ?? '')],
                    static fn(string $sid): bool => $sid !== ''
                ));
                $current = Stream::get_latest_now_playing($sessionIds);
                if ($current !== null) {
                    $className = ObjectTypeToClassNameMapper::map($current['object_type']);
                    /** @var Song|Video $media */
                    $media = new $className($current['object_id']);
                    if (!$media->isNew() && $media instanceof Song) {
                        $web_path   = (string) AmpConfig::get_web_path();
                        $artistId   = (int) $media->artist;
                        $albumId    = (int) $media->album;
                        $titleText  = scrub_out((string) $media->get_fullname());
                        $artistText = scrub_out(Artist::get_fullname_by_id($artistId));
                        $albumText  = scrub_out((string) $media->get_album_fullname());

                        // per-song action row (album button + rating/flag placeholder) mirroring the regular
                        // playlist flow in show_html5_player.inc.php, which random/democratic streams skip
                        $showAlbum = T_('Show Album');
                        $actions   = ($albumId > 0)
                            ? '<a href="javascript:NavigateTo(\'' . $web_path . '/albums.php?action=show&album=' . $albumId . '\')" title="' . $showAlbum . '">' . Ui::get_material_symbol('album', $showAlbum) . '</a> | '
                            : '';
                        $actions .= "<div id='action_buttons'></div>";

                        $data = [
                            'found' => true,
                            'object_type' => $current['object_type'],
                            'object_id' => $current['object_id'],
                            'title' => '<a href="javascript:NavigateTo(\'' . $web_path . '/song.php?action=show_song&song_id=' . $media->id . '\')" title="' . $titleText . '">' . $titleText . '</a>',
                            'artist' => ($artistId > 0)
                                ? '<a href="javascript:NavigateTo(\'' . $web_path . '/artists.php?action=show&artist=' . $artistId . '\')">' . $artistText . '</a>'
                                : $artistText,
                            'album' => ($albumId > 0)
                                ? '<a href="javascript:NavigateTo(\'' . $web_path . '/albums.php?action=show&album=' . $albumId . '\')">' . $albumText . '</a>'
                                : $albumText,
                            'art' => (string) (Art::url($albumId, 'album', null) ?? ''),
                            'actions' => $actions,
                        ];
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($data);

                return;
            case 'show_broadcasts':
                ob_start();
                $ajaxUri = $this->ajaxUriRetriever->getAjaxUri();
                $this->ui->show(
                    'show_broadcasts_dialog.inc.php',
                    ['ajaxUri' => $ajaxUri]
                );
                $results = ob_get_contents();
                ob_end_clean();
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
                    $results['broadcast'] = Broadcast::get_unbroadcast_link((int) $broadcast_id) . "<script>startBroadcast('" . $key . "');</script>";
                }

                break;
            case 'unbroadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                $broadcast    = new Broadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $broadcast->update_state(0);
                    $results['broadcast'] = Broadcast::get_broadcast_link() . '<script>stopBroadcast();</script>';
                }
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
