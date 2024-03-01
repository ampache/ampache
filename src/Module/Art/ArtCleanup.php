<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Art;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Art;

/**
 * Provides methods for the cleanup/deletion of art-items
 */
final class ArtCleanup implements ArtCleanupInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * look for art in the image table that doesn't fit min or max dimensions and delete it
     */
    public function cleanup(): void
    {
        $minw = $this->configContainer->get('album_art_min_width') ?? null;
        $maxw = $this->configContainer->get('album_art_max_width') ?? null;
        $minh = $this->configContainer->get('album_art_min_height') ?? null;
        $maxh = $this->configContainer->get('album_art_max_height') ?? null;

        // minimum width is set and current width is too low
        if ($minw) {
            $sql = 'DELETE FROM `image` WHERE `width` < ? AND `width` > 0';
            Dba::write($sql, array($minw));
        }
        // max width is set and current width is too high
        if ($maxw) {
            $sql = 'DELETE FROM `image` WHERE `width` > ? AND `width` > 0';
            Dba::write($sql, array($maxw));
        }
        // min height is set and current width is too low
        if ($minh) {
            $sql = 'DELETE FROM `image` WHERE `height` < ? AND `height` > 0';
            Dba::write($sql, array($minh));
        }
        // max height is set and current height is too high
        if ($maxh) {
            $sql = 'DELETE FROM `image` WHERE `height` > ? AND `height` > 0';
            Dba::write($sql, array($maxh));
        }
    }

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbageForObject(string $object_type, int $object_id): void
    {
        $types = array(
            'album',
            'album_disk',
            'artist',
            'catalog',
            'tag',
            'label',
            'live_stream',
            'playlist',
            'podcast',
            'podcast_episode',
            'song',
            'tvshow',
            'tvshow_season',
            'user',
            'video'
        );

        if (in_array($object_type, $types)) {
            if ($this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK)) {
                Art::delete_from_dir($object_type, $object_id);
            }
            $sql = "DELETE FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            Dba::write($sql, array($object_type, $object_id));
        } else {
            debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
        }
    }

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbage(): void
    {
        $types = array(
            'album',
            'album_disk',
            'artist',
            'catalog',
            'tag',
            'label',
            'live_stream',
            'playlist',
            'podcast',
            'podcast_episode',
            'song',
            'tvshow',
            'tvshow_season',
            'user',
            'video'
        );

        $album_art_store_disk = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK);
        // iterate over our types and delete the images
        foreach ($types as $type) {
            if ($album_art_store_disk) {
                $sql        = "SELECT `image`.`object_id`, `image`.`object_type` FROM `image` LEFT JOIN `" . $type . "` ON `" . $type . "`.`id`=" . "`image`.`object_id` WHERE `object_type`='" . $type . "' AND `" . $type . "`.`id` IS NULL";
                $db_results = Dba::read($sql);
                while ($row = Dba::fetch_row($db_results)) {
                    Art::delete_from_dir($row[1], (int)$row[0]);
                }
            }
            $sql = "DELETE FROM `image` USING `image` LEFT JOIN `" . $type . "` ON `" . $type . "`.`id`=" . "`image`.`object_id` WHERE `object_type`='" . $type . "' AND `" . $type . "`.`id` IS NULL";
            Dba::write($sql);
        }
    }

    /**
     * This resets the art in the database
     */
    public function deleteForArt(Art $art): void
    {
        if ($this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK)) {
            Art::delete_from_dir($art->type, $art->uid, $art->kind);
        }
        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `kind` = ?";
        Dba::write($sql, array($art->uid, $art->type, $art->kind));
    }
}
