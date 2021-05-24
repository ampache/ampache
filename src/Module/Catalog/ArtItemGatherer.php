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

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Video\VideoLoaderInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;

final class ArtItemGatherer implements ArtItemGathererInterface
{
    private ModelFactoryInterface $modelFactory;

    private ArtCollectorInterface $artCollector;

    private VideoLoaderInterface $videoLoader;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ArtCollectorInterface $artCollector,
        VideoLoaderInterface $videoLoader
    ) {
        $this->modelFactory = $modelFactory;
        $this->artCollector = $artCollector;
        $this->videoLoader  = $videoLoader;
    }

    public function gather(
        string $type,
        int $objectId,
        bool $dbArtFirst = false,
        bool $api = false
    ): bool {
        // Should be more generic !
        if ($type == 'video') {
            $libitem = $this->videoLoader->load($objectId);
        } else {
            $libitem = $this->modelFactory->mapObjectType($type, $objectId);
        }
        $inserted = false;
        $options  = array();
        $libitem->format();
        if ($libitem->id) {
            if (count($options) == 0) {
                // Only search on items with default art kind as `default`.
                if ($libitem->get_default_art_kind() == 'default') {
                    $keywords = $libitem->get_keywords();
                    $keyword  = '';
                    foreach ($keywords as $key => $word) {
                        $options[$key] = $word['value'];
                        if ($word['important'] && !empty($word['value'])) {
                            $keyword .= ' ' . $word['value'];
                        }
                    }
                    $options['keyword'] = $keyword;
                }

                $parent = $libitem->get_parent();
                if (!empty($parent)) {
                    $this->gather($parent['object_type'], $parent['object_id'], $dbArtFirst, $api);
                }
            }
        }

        $art = $this->modelFactory->createArt($objectId, $type);
        // don't search for art when you already have it
        if ($art->has_db_info() && $dbArtFirst) {
            debug_event(self::class, 'Blocking art search for ' . $type . '/' . $objectId . ' DB item exists', 5);
            $results = array();
        } else {
            debug_event(__CLASS__, 'Gathering art for ' . $type . '/' . $objectId . '...', 4);

            $results = $this->artCollector->collect(
                $art,
                $options
            );
        }

        foreach ($results as $result) {
            // Pull the string representation from the source
            $image = Art::get_from_source($result, $type);
            if (strlen((string)$image) > '5') {
                $inserted = $art->insert($image, $result['mime']);
                // If they've enabled resizing of images generate a thumbnail
                if (AmpConfig::get('resize_images')) {
                    $size  = array('width' => 275, 'height' => 275);
                    $thumb = $art->generate_thumb($image, $size, $result['mime']);
                    if (!empty($thumb)) {
                        $art->save_thumb($thumb['thumb'], $thumb['thumb_mime'], $size);
                    }
                }
                if ($inserted) {
                    break;
                }
            } elseif ($result === true) {
                debug_event(self::class, 'Database already has image.', 3);
            } else {
                debug_event(self::class, 'Image less than 5 chars, not inserting', 3);
            }
        }

        if ($type == 'video' && AmpConfig::get('generate_video_preview')) {
            Video::generate_preview($objectId);
        }

        if (Ui::check_ticker() && !$api) {
            Ui::update_text('read_art_' . $objectId, $libitem->get_fullname());
        }
        if ($inserted) {
            return true;
        }

        return false;
    }
}
