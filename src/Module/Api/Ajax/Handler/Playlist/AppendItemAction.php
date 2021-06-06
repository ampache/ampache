<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Playlist;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AppendItemAction implements ActionInterface
{
    private PlaylistRepositoryInterface $playlistRepository;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        PlaylistRepositoryInterface $playlistRepository,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->modelFactory       = $modelFactory;
        $this->ui                 = $ui;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        // Only song item are supported with playlists
        if (!isset($_REQUEST['playlist_id']) || empty($_REQUEST['playlist_id'])) {
            if (!Access::check('interface', 25)) {
                debug_event('playlist.ajax', 'Error:' . Core::get_global('user')->username . ' does not have user access, unable to create playlist', 1);

                return [];
            }

            $name        = $_REQUEST['name'];
            if (empty($name)) {
                $name = Core::get_global('user')->username . ' - ' . get_datetime(time());
            }
            $playlist_id = $this->playlistRepository->create(
                $name,
                'private',
                Core::get_global('user')->getId()
            );
            if ($playlist_id < 1) {
                return [];
            }
            $playlist = new Playlist($playlist_id);
        } else {
            $playlist = new Playlist($_REQUEST['playlist_id']);
        }

        if (!$playlist->has_access()) {
            return [];
        }
        debug_event('playlist.ajax', 'Appending items to playlist {' . $playlist->id . '}...', 5);

        $medias    = array();
        $item_id   = $_REQUEST['item_id'];
        $item_type = $_REQUEST['item_type'];

        if (!empty($item_type) && InterfaceImplementationChecker::is_playable_item($item_type)) {
            debug_event('playlist.ajax', 'Adding all medias of ' . $item_type . '(s) {' . $item_id . '}...', 5);
            $item_ids = explode(',', $item_id);
            foreach ($item_ids as $iid) {
                /** @var library_item $libitem */
                $libitem = $this->modelFactory->mapObjectType($item_type, (int) $iid);
                $medias  = array_merge($medias, $libitem->get_medias());
            }
        } else {
            debug_event('playlist.ajax', 'Adding all medias of current playlist...', 5);
            $medias = Core::get_global('user')->playlist->get_items();
        }

        if (count($medias) > 0) {
            Ajax::set_include_override(true);
            $playlist->add_medias($medias, (bool) AmpConfig::get('unique_playlist'));

            debug_event('playlist.ajax', 'Items added successfully!', 5);
            $results['rfc3514'] = $this->ui->displayNotification(T_('Added to playlist'));
        } else {
            debug_event('playlist.ajax', 'No item to add. Aborting...', 5);
        }

        return $results;
    }
}
