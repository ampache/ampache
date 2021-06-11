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

declare(strict_types=0);

namespace Ampache\Module\Catalog;

use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogLoadingException;
use Ampache\Module\Song\Tag\SongFromTagUpdaterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\VaInfo;
use Ampache\Module\Video\VideoFromTagUpdaterInterface;
use Ampache\Repository\Model\PlayableMediaInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Log\LoggerInterface;

/**
 * This is a 'wrapper' function calls the update function for the media
 * type in question
 */
final class MediaFromTagUpdater implements MediaFromTagUpdaterInterface
{
    private CatalogLoaderInterface $catalogLoader;

    private SongFromTagUpdaterInterface $songFromTagUpdater;

    private VideoFromTagUpdaterInterface $videoFromTagUpdater;

    private LoggerInterface $logger;

    public function __construct(
        CatalogLoaderInterface $catalogLoader,
        SongFromTagUpdaterInterface $songFromTagUpdater,
        VideoFromTagUpdaterInterface $videoFromTagUpdater,
        LoggerInterface $logger
    ) {
        $this->catalogLoader       = $catalogLoader;
        $this->songFromTagUpdater  = $songFromTagUpdater;
        $this->videoFromTagUpdater = $videoFromTagUpdater;
        $this->logger              = $logger;
    }

    /**
     * This is a 'wrapper' function calls the update function for the media
     * type in question
     *
     * @param array<string> $gatherTypes
     *
     * @return array<string, mixed>
     */
    public function update(
        PlayableMediaInterface $media,
        array $gatherTypes = ['music'],
        string $sortPattern = '',
        string $renamePattern = ''
    ) {
        try {
            $catalog = $this->catalogLoader->byId($media->getCatalogId());
        } catch (CatalogLoadingException $e) {
            $this->logger->error(
                'update_media_from_tags: Error loading catalog ' . $media->getCatalogId(),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return [];
        }

        $functions = [
            Song::class => fn ($results, $media) => $this->songFromTagUpdater->update($results, $media),
            Video::class => fn ($results, $media) => $this->videoFromTagUpdater->update($results, $media),
        ];

        $type = get_class($media) === Song::class ? Song::class : Video::class;

        $callable = $functions[$type];

        // try and get the tags from your file
        $extension    = strtolower(pathinfo($media->getFile(), PATHINFO_EXTENSION));
        $results      = $catalog->get_media_tags($media, $gatherTypes, $sortPattern, $renamePattern);
        // for files without tags try to update from their file name instead
        if ($media->getId() && in_array($extension, ['wav', 'shn'])) {
            $this->logger->error(
                sprintf('update_media_from_tags: %s extension: parse_pattern', $extension),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            // match against your catalog 'Filename Pattern' and 'Folder Pattern'
            $patres  = vainfo::parse_pattern($media->getFile(), $catalog->sort_pattern, $catalog->rename_pattern);
            $results = array_merge($results, $patres);
        }
        $this->logger->info(
            'Reading tags from ' . $media->getFile(),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        return $callable($results, $media);
    }
}
