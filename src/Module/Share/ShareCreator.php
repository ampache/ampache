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

namespace Ampache\Module\Share;

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;

/**
 * Create share items
 */
final class ShareCreator implements ShareCreatorInterface
{
    public function create(
        string $object_type,
        int $object_id,
        bool $allow_stream = true,
        bool $allow_download = true,
        int $expire = 0,
        string $secret = '',
        int $max_counter = 0,
        string $description = ''
    ): ?int {
        $object_type = Share::format_type($object_type);
        if (empty($object_type)) {
            return null;
        }
        if (!$allow_stream && !$allow_download) {
            return null;
        }

        if ($description == '') {
            if ($object_type == 'song') {
                $song        = new Song($object_id);
                $description = $song->title;
            } elseif ($object_type == 'playlist') {
                $playlist    = new Playlist($object_id);
                $description = 'Playlist - ' . $playlist->name;
            } elseif ($object_type == 'album') {
                $album = new Album($object_id);
                $album->format();
                $description = $album->f_name . ' (' . $album->f_album_artist_name . ')';
            }
        }
        $sql    = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            Core::get_global('user')->id,
            $object_type,
            $object_id,
            time(),
            $allow_stream ?: 0,
            $allow_download ?: 0,
            $expire,
            $secret,
            0,
            $max_counter,
            $description
        );
        Dba::write($sql, $params);

        $share_id = Dba::insert_id();

        $url = Share::get_url($share_id, $secret);
        // Get a shortener url if any available
        foreach (Plugin::get_plugins('shortener') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load(Core::get_global('user'))) {
                    $short_url = $plugin->_plugin->shortener($url);
                    if (!empty($short_url)) {
                        $url = $short_url;
                        break;
                    }
                }
            } catch (\Exception $error) {
                debug_event(self::class, 'Share plugin error: ' . $error->getMessage(), 1);
            }
        }
        $sql = "UPDATE `share` SET `public_url` = ? WHERE `id` = ?";
        Dba::write($sql, array($url, $share_id));

        return (int) $share_id;
    }
}
