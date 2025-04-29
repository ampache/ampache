<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

final readonly class PlaylistAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser
    ) {
    }

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'delete_track':
                // Create the object and remove the track
                $playlist = new Playlist($_REQUEST['playlist_id']);
                if ($playlist->isNew()) {
                    break;
                }

                if ($playlist->has_collaborate()) {
                    $playlist->delete_track($_REQUEST['track_id']);
                    // This could have performance issues
                    $playlist->regenerate_track_numbers();
                }

                $browse_id  = (int)($_REQUEST['browse_id'] ?? 0);
                $object_ids = $playlist->get_items();
                ob_start();
                $browse = new Browse($browse_id);
                $browse->set_type('playlist_media');
                $browse->add_supplemental_object('playlist', $playlist->id);
                $browse->save_objects($object_ids);
                $browse->show_objects($object_ids);
                $browse->store();

                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'append_item':
                // Only song item are supported with playlists
                if (empty($_REQUEST['playlist_id'])) {
                    if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
                        debug_event('playlist.ajax', 'Error:' . $user->username . ' does not have user access, unable to create playlist', 1);
                        break;
                    }

                    $name = $_REQUEST['name'] ?? '';
                    if (empty($name)) {
                        $name = $user->username . ' - ' . get_datetime(time());
                    }

                    $playlist_id = (int)Playlist::create($name, 'public');
                    if ($playlist_id < 1) {
                        break;
                    }

                    $playlist = new Playlist($playlist_id);
                } else {
                    $playlist = new Playlist($_REQUEST['playlist_id']);
                }

                if (!$playlist->has_collaborate()) {
                    break;
                }

                debug_event('playlist.ajax', 'Appending items to playlist {' . $playlist->id . '}...', 5);

                $medias    = [];
                $item_id   = $_REQUEST['item_id'] ?? '';
                $item_type = $_REQUEST['item_type'] ?? '';

                if (!empty($item_type) && InterfaceImplementationChecker::is_playable_item($item_type)) {
                    debug_event('playlist.ajax', 'Adding all medias of ' . $item_type . '(s) {' . $item_id . '}...', 5);
                    $item_ids = explode(',', (string) $item_id);
                    foreach ($item_ids as $iid) {
                        $className = ObjectTypeToClassNameMapper::map($item_type);
                        /** @var library_item $libitem */
                        $libitem = new $className($iid);
                        if ($libitem->isNew() === false) {
                            $medias = array_merge($medias, $libitem->get_medias());
                        }
                    }
                } else {
                    debug_event('playlist.ajax', 'Adding all medias of current playlist...', 5);
                    $medias = $user->playlist?->get_items() ?? [];
                }

                if (
                    count($medias) > 0 &&
                    $playlist->add_medias($medias)
                ) {
                    Ajax::set_include_override(true);

                    debug_event('playlist.ajax', 'Items added successfully!', 5);
                    ob_start();
                    display_notification(T_('Added to playlist'));
                    $results['reloader'] = ob_get_clean();
                } else {
                    debug_event('playlist.ajax', 'No item to add. Aborting...', 5);
                }
        }

        echo (string) xoutput_from_array($results);
    }
}
