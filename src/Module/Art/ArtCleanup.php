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

namespace Ampache\Module\Art;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\library_item;

/**
 * Provides methods for the cleanup/deletion of art-items
 */
final class ArtCleanup implements ArtCleanupInterface
{
    private ConfigContainerInterface $configContainer;

    private const TYPES = [
        'album_disk',
        'album',
        'artist',
        'catalog',
        'label',
        'live_stream',
        'playlist',
        'podcast_episode',
        'podcast',
        'song',
        'tag',
        'user',
        'video',
    ];

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
            Dba::write($sql, [$minw]);
        }
        // max width is set and current width is too high
        if ($maxw) {
            $sql = 'DELETE FROM `image` WHERE `width` > ? AND `width` > 0';
            Dba::write($sql, [$maxw]);
        }
        // min height is set and current width is too low
        if ($minh) {
            $sql = 'DELETE FROM `image` WHERE `height` < ? AND `height` > 0';
            Dba::write($sql, [$minh]);
        }
        // max height is set and current height is too high
        if ($maxh) {
            $sql = 'DELETE FROM `image` WHERE `height` > ? AND `height` > 0';
            Dba::write($sql, [$maxh]);
        }
    }

    /**
     * clean up the local metadata folder by moving thumbnails to their correct location
     */
    public function migrateThumbnails(Interactor $interactor, bool $delete): void
    {
        $metadata_dir = $this->configContainer->get(ConfigurationKeyEnum::LOCAL_METADATA_DIR);
        if ($metadata_dir) {
            // Now check for orhpaned art files
            $interactor->info(
                'Checking for orhpaned art files',
                true
            );

            $types = self::TYPES;

            foreach ($types as $type) {
                $type_path = $metadata_dir . DIRECTORY_SEPARATOR . $type;
                if (!Core::is_readable($type_path)) {
                    continue;
                }

                $object_dirs = scandir($type_path);
                if ($object_dirs === false) {
                    continue;
                }

                foreach ($object_dirs as $object_id) {
                    if ($object_id === '.' || $object_id === '..') {
                        continue;
                    }

                    $object_path = $type_path . DIRECTORY_SEPARATOR . $object_id . DIRECTORY_SEPARATOR . 'default';
                    if (!is_dir($object_path)) {
                        continue;
                    }

                    $files = scandir($object_path);
                    if ($files === false || $files === ['.', '..']) {
                        continue;
                    }

                    // check if this even exists in the database
                    $className = ObjectTypeToClassNameMapper::map($type);
                    $item      = new $className($object_id);
                    /** @var library_item $item */
                    $exists = $item->isNew() === false;
                    if (!$exists) {
                        $interactor->info(
                            sprintf(
                                'Object does not exist: %s/%s',
                                $type,
                                $object_id
                            ),
                            true
                        );
                    }

                    foreach ($files as $file) {
                        // Look for art files with size in the filename (e.g., art-128x128.jpg)
                        if (preg_match('/^art-(\d+x\d+)\./', $file, $matches)) {
                            if (!$exists) {
                                if ($delete) {
                                    unlink($object_path . DIRECTORY_SEPARATOR . $file);
                                    $interactor->info(
                                        sprintf(
                                            'DELETE: %s',
                                            $object_path . DIRECTORY_SEPARATOR . $file
                                        ),
                                        true
                                    );
                                }
                                continue;
                            }
                            $size = $matches[1];
                            if (!Art::has_db((int)$object_id, $type, 'default', $size)) {
                                if ($delete) {
                                    unlink($object_path . DIRECTORY_SEPARATOR . $file);
                                    $interactor->info(
                                        sprintf(
                                            'DELETE: %s',
                                            $object_path . DIRECTORY_SEPARATOR . $file
                                        ),
                                        true
                                    );
                                } else {
                                    $interactor->info(
                                        sprintf(
                                            'Thumbnail is not in the database: %s/%s/%s (size: %s)',
                                            $type,
                                            $object_id,
                                            $file,
                                            $size
                                        ),
                                        true
                                    );
                                }
                            }
                        }
                        if (preg_match('/^art-(original)\./', $file, $matches)) {
                            if (!$exists) {
                                if ($delete) {
                                    unlink($object_path . DIRECTORY_SEPARATOR . $file);
                                    $interactor->info(
                                        sprintf(
                                            'DELETE: %s',
                                            $object_path . DIRECTORY_SEPARATOR . $file
                                        ),
                                        true
                                    );
                                }
                                continue;
                            }
                            $size = $matches[1];
                            if (!Art::has_db((int)$object_id, $type, 'default', $size)) {
                                if ($delete) {
                                    unlink($object_path . DIRECTORY_SEPARATOR . $file);
                                    $interactor->info(
                                        sprintf(
                                            'DELETE: %s',
                                            $object_path . DIRECTORY_SEPARATOR . $file
                                        ),
                                        true
                                    );
                                } else {
                                    $interactor->info(
                                        sprintf(
                                            'Image is not in the database: %s/%s/%s (size: %s)',
                                            $type,
                                            $object_id,
                                            $file,
                                            $size
                                        ),
                                        true
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if ($delete) {
                $interactor->info(
                    'Migrating thumbnails to their correct location',
                    true
                );
                $sql        = "SELECT `object_id`, `object_type`, `kind`, `size`, `mime` FROM `image` WHERE `size` != 'original'";
                $db_results = Dba::read($sql);
                while ($row = Dba::fetch_assoc($db_results)) {
                    $art_path = Art::get_dir_on_disk($row['object_type'], (int)$row['object_id'], $row['size'], $row['kind'], true);
                    $old_path = Art::get_dir_on_disk($row['object_type'], (int)$row['object_id'], 'original', $row['kind']);

                    $art_path .= "art-" . $row['size'] . "." . Art::extension($row['mime']);
                    $old_path .= "art-" . $row['size'] . "." . Art::extension($row['mime']);
                    if (Core::is_readable($old_path) && !Core::is_readable($art_path)) {
                        rename($old_path, $art_path);
                    } elseif (Core::is_readable($old_path)) {
                        unlink($old_path);
                    }
                }
            }

            $interactor->info(
                'Delete art that is missing on disk',
                true
            );
            $sql        = "SELECT `object_id`, `object_type`, `kind`, `size`, `mime` FROM `image`;";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $art_path = Art::get_dir_on_disk($row['object_type'], (int)$row['object_id'], $row['size'], $row['kind'], true);
                $art_path .= "art-" . $row['size'] . "." . Art::extension($row['mime']);
                if (!Core::is_readable($art_path)) {
                    if ($delete) {
                        // If this art is gone stop trying to find it
                        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `kind` = ? AND `size` = ?";
                        Dba::write($sql, [(int)$row['object_id'], $row['object_type'], $row['kind'], $row['size']], true);
                        $interactor->info(
                            sprintf(
                                'DELETE: %s/%s',
                                $row['object_type'],
                                $row['object_id']
                            ),
                            true
                        );
                    } else {
                        $interactor->info(
                            sprintf(
                                'Database Art is missing on disk: %s/%s (size: %s, kind: %s)',
                                $row['object_type'],
                                $row['object_id'],
                                $row['size'],
                                $row['kind']
                            ),
                            true
                        );
                    }
                }
            }
        } else {
            $interactor->error(
                'No local metadata directory configured, skipping thumbnail migration',
                true
            );
        }
    }

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbageForObject(string $object_type, int $object_id): void
    {
        $types = self::TYPES;

        $album_art_store_disk = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK);
        if (in_array($object_type, $types)) {
            if ($album_art_store_disk) {
                Art::delete_from_dir($object_type, $object_id);
            }
            $sql = "DELETE FROM `image` WHERE `object_type` = ? AND `object_id` = ?";
            Dba::write($sql, [$object_type, $object_id], true);
        } else {
            debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
        }
    }

    /**
     * This cleans up art that no longer has a corresponding object
     */
    public function collectGarbage(): void
    {
        $types = self::TYPES;

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
            Dba::write($sql, [], true);
        }
    }

    /**
     * This resets the art in the database
     */
    public function deleteForArt(Art $art): void
    {
        if ($this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK)) {
            Art::delete_from_dir($art->object_type, $art->object_id, $art->kind);
        }
        $sql = "DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `kind` = ?";
        Dba::write($sql, [$art->object_id, $art->object_type, $art->kind]);
    }

    /**
     * Remove all thumbnail art in the database keeping original images
     */
    public function deleteThumbnails(Interactor $interactor, bool $delete): void
    {
        $sql        = "SELECT * FROM `image` WHERE `size` != 'original';";
        $db_results = Dba::read($sql);
        $thumbnails = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $thumbnails[] = [
                'id' => $row['id'],
                'object_id' => $row['object_id'],
                'object_type' => $row['object_type'],
                'kind' => $row['kind'],
                'size' => $row['size'],
                'mime' => $row['mime'],
            ];
        }

        $interactor->info(
            'Found ' . count($thumbnails) . ' thumbnails to delete',
            true
        );

        if ($delete) {
            $album_art_store_disk = $this->configContainer->get(ConfigurationKeyEnum::ALBUM_ART_STORE_DISK);
            foreach ($thumbnails as $thumbnail) {
                if ($album_art_store_disk) {
                    Art::delete_from_dir($thumbnail['object_type'], $thumbnail['object_id'], $thumbnail['kind'], $thumbnail['size'], $thumbnail['mime']);
                }
                $sql = "DELETE FROM `image` WHERE `id` = ? AND `size` != 'original'";
                Dba::write($sql, [$thumbnail['id']]);
            }
        }
    }
}
