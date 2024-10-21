<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\Share;

use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Creates new sharing items
 */
final class ShareCreator implements ShareCreatorInterface
{
    private PluginRetrieverInterface $pluginRetriever;

    private LoggerInterface $logger;

    public function __construct(
        PluginRetrieverInterface $pluginRetriever,
        LoggerInterface $logger
    ) {
        $this->pluginRetriever = $pluginRetriever;
        $this->logger          = $logger;
    }

    public function create(
        User $user,
        string $object_type,
        int $object_id,
        bool $allow_stream = true,
        bool $allow_download = true,
        int $expire_days = 0,
        string $secret = '',
        int $max_counter = 0,
        ?string $description = ''
    ): ?int {
        if (!Share::is_valid_type($object_type)) {
            $this->logger->error(
                'create_share: Bad object_type ' . $object_type,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        if (
            !$allow_stream &&
            !$allow_download
        ) {
            $this->logger->error(
                'create_share: must allow stream OR allow download',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        if ($description === '') {
            if ($object_type == 'song') {
                $song        = new Song($object_id);
                $description = $song->title;
            } elseif ($object_type == 'playlist') {
                $playlist    = new Playlist($object_id);
                $description = 'Playlist - ' . $playlist->name;
            } elseif ($object_type == 'album') {
                $album = new Album($object_id);
                $album->format();
                $description = $album->get_fullname() . ' (' . $album->get_artist_fullname() . ')';
            } elseif ($object_type == 'album_disk') {
                $albumdisk = new AlbumDisk($object_id);
                $albumdisk->format();
                $description = $albumdisk->get_fullname() . ' (' . $albumdisk->get_artist_fullname() . ')';
            }
        }
        $sql    = "INSERT INTO `share` (`user`, `object_type`, `object_id`, `creation_date`, `allow_stream`, `allow_download`, `expire_days`, `secret`, `counter`, `max_counter`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $user->getId(),
            $object_type,
            $object_id,
            time(),
            (int)$allow_stream,
            (int)$allow_download,
            $expire_days,
            $secret,
            0,
            $max_counter,
            $description
        ];
        Dba::write($sql, $params);

        $share_id = (int)
        Dba::insert_id();

        $url = Share::get_url((int)$share_id, $secret);
        // Get a shortener url if any available
        foreach ($this->pluginRetriever->retrieveByType('shortener', $user) as $plugin) {
            try {
                /** @var string|false $short_url */
                $short_url = $plugin->_plugin->shortener($url);
                if (!empty($short_url)) {
                    $url = $short_url;
                    break;
                }
            } catch (Exception $error) {
                $this->logger->critical(
                    'Share plugin error: ' . $error->getMessage(),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }

        $sql = "UPDATE `share` SET `public_url` = ? WHERE `id` = ?";
        Dba::write($sql, [$url, $share_id]);

        return $share_id;
    }
}
