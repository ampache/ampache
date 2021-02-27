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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Plugin\Adapter;

use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Exception;

/**
 * Registers the playback of a certain song in connected plugins
 */
final class UserMediaPlaySaverAdapter implements UserMediaPlaySaverAdapterInterface
{
    public function save(
        User $user,
        Song $song
    ): void {
        foreach (Plugin::get_plugins('save_mediaplay') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($user)) {
                    debug_event(self::class, 'save_mediaplay... ' . $plugin->_plugin->name, 5);
                    $plugin->_plugin->save_mediaplay($song);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_mediaplay plugin error: ' . $error->getMessage(), 1);
            }
        }
    }
}
