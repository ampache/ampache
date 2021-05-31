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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\SongRepositoryInterface;
use Exception;
use getID3;
use Psr\Log\LoggerInterface;

final class MetaTagCollectorModule implements CollectorModuleInterface
{
    private LoggerInterface $logger;

    private getID3 $getID3;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        LoggerInterface $logger,
        getID3 $getID3,
        SongRepositoryInterface $songRepository
    ) {
        $this->logger         = $logger;
        $this->getID3         = $getID3;
        $this->songRepository = $songRepository;
    }

    /**
     * This looks for the art in the meta-tags of the file
     * itself
     *
     * @param Art $art
     * @param integer $limit
     * @param array $data
     *
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ):array {
        if (!$limit) {
            $limit = 5;
        }

        if ($art->type == "video") {
            $data = $this->gatherVideoTags($art);
        } elseif ($art->type == 'album' || $art->type == 'artist') {
            $data = $this->gatherSongTags($art, $limit);
        } elseif (($art->type == 'song') && (AmpConfig::get('gather_song_art', false))) {
            $data = $this->gatherSongTagsSingle($art, $limit);
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * Gather tags from video files.
     */
    private function gatherVideoTags(Art $art): array
    {
        $video = new Video($art->uid);

        return $this->gatherMediaTags($video);
    }

    /**
     * Gather tags from audio files.
     * @param Art $art
     * @param integer $limit
     * @return array
     */
    private function gatherSongTags(Art $art, int $limit = 5): array
    {
        // We need the filenames
        if ($art->type == 'album') {
            $songs = $this->songRepository->getByAlbum($art->uid);
        } else {
            $songs  = $this->songRepository->getByArtist($art->uid);
        }

        $data  = [];

        // Foreach songs in this album
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $data = array_merge($data, $this->gatherMediaTags($song));

            if ($limit && count($data) >= $limit) {
                return array_slice($data, 0, $limit);
            }
        }

        return $data;
    }

    /**
     * Gather tags from files.
     * @param Song|Video $media
     * @return array
     */
    private function gatherMediaTags($media)
    {
        $mtype  = ObjectTypeToClassNameMapper::reverseMap(get_class($media));
        $data   = [];
        try {
            $id3 = $this->getID3->analyze($media->getFile());
        } catch (Exception $error) {
            $this->logger->error(
                'getid3' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
            $image  = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
            $data[] = array(
                $mtype => $media->getFile(),
                'raw' => $image['data'],
                'mime' => $image['mime'],
                'title' => 'ID3'
            );
        }

        if (isset($id3['id3v2']['APIC'])) {
            // Foreach in case they have more than one
            foreach ($id3['id3v2']['APIC'] as $image) {
                $this_picturetypeid = ($mtype == 'artist') ? 8 : 3;
                if ($image['picturetypeid'] == $this_picturetypeid) {
                    $data[] = [
                        $mtype => $media->getFile(),
                        'raw' => $image['data'],
                        'mime' => $image['mime'],
                        'title' => 'ID3'
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Gather tags from single song instead of full album
     * (taken from function gather_song_tags with some changes)
     * @param integer $limit
     * @return array
     */
    public function gatherSongTagsSingle(Art $art, $limit = 5)
    {
        // get song object directly from id, not by loop through album
        $song = new Song($art->uid);
        $data = array();
        $data = array_merge($data, $this->gatherMediaTags($song));

        if ($limit && count($data) >= $limit) {
            return array_slice($data, 0, $limit);
        }

        return $data;
    }
}
