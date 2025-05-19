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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;
use Exception;
use getID3;
use Psr\Log\LoggerInterface;

final class MetaTagCollectorModule implements CollectorModuleInterface
{
    private const TAG_ALBUM_ART_PRIORITY = [
        'ID3 Front Cover',
        'ID3 Illustration',
        'ID3 Media',
    ];

    private const TAG_ARTIST_ART_PRIORITY = [
        'ID3 Artist',
        'ID3 Lead Artist',
        'ID3 Band',
        'ID3 Conductor',
        'ID3 Composer',
        'ID3 Lyricist',
        'ID3 Other',
    ];

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
     * This looks for the art in the meta-tags of the file itself
     *
     * @param Art $art
     * @param int $limit
     * @param array{
     *      mb_albumid?: string,
     *      artist?: string,
     *      album?: string,
     *      cover?: ?string,
     *      file?: string,
     *      year_filter?: string,
     *      search_limit?: int,
     *  } $data
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        if (!$limit) {
            $limit = 5;
        }

        if ($art->object_type == 'video') {
            $data = $this->gatherVideoTags($art);
        } elseif ($art->object_type == 'album' || $art->object_type == 'artist') {
            $data = $this->gatherSongTags($art, $limit);
        } elseif (($art->object_type == 'song') && (AmpConfig::get('gather_song_art', false))) {
            $data = $this->gatherSongTagsSingle($art, $limit);
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * Calculate the priority for the given art type.
     */
    private static function getArtTypePriority(string $type, array $priorities): int
    {
        $priority = array_search($type, $priorities);
        if ($priority === false) {
            return sizeof($priorities);
        }

        return (int)$priority;
    }

    /**
     * Sort images in the data array using the ART_PRIORITY list for your art_type
     * @param array $data
     * @param string $art_type
     * @return array
     */
    private static function sortArtByPriority(array $data, string $art_type): array
    {
        $priorities = ($art_type === 'artist')
            ? self::TAG_ARTIST_ART_PRIORITY
            : self::TAG_ALBUM_ART_PRIORITY; // song and album art
        uasort(
            $data,
            function ($image1, $image2) use (&$priorities) {
                return self::getArtTypePriority($image1['title'], $priorities) <=> self::getArtTypePriority($image2['title'], $priorities);
            }
        );

        return $data;
    }

    /**
     * Gather tags from video files.
     */
    private function gatherVideoTags(Art $art): array
    {
        $video = new Video($art->object_id);

        return $this->gatherMediaTags($video, []);
    }

    /**
     * Gather tags from audio files.
     * @param Art $art
     * @param int $limit
     * @return array
     */
    private function gatherSongTags(Art $art, int $limit = 5): array
    {
        // We need the filenames
        if ($art->object_type == 'album') {
            $songs = $this->songRepository->getByAlbum($art->object_id);
        } else {
            $songs = $this->songRepository->getByArtist($art->object_id);
        }

        $data = [];

        // Foreach songs in this album
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $data = $this->gatherMediaTags($song, $data);

            if ($limit && count($data) >= $limit) {
                break;
            }
        }

        $data = self::sortArtByPriority($data, $art->object_type);

        if ($limit && count($data) >= $limit) {
            $data = array_slice($data, 0, $limit);
        }

        return $data;
    }

    /**
     * Gather all art from tags in given file.
     * @return array<int, array{raw: string, mime: string, title: string}>
     */
    public static function gatherFileArt(string $file): array
    {
        try {
            $getID3 = new getID3();
            $id3    = $getID3->analyze($file);
        } catch (Exception $error) {
            debug_event(self::class, 'getid3' . $error->getMessage(), 2);

            return [];
        }

        $images = [];

        if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
            $image = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
            if (array_key_exists('data', $image)) {
                $images[] = [
                    'raw' => $image['data'],
                    'mime' => $image['mime'],
                    'title' => 'ID3 asf'
                ];
            }
        }

        if (isset($id3['id3v2']['APIC'])) {
            // Foreach in case they have more than one
            foreach ($id3['id3v2']['APIC'] as $image) {
                if (isset($image['picturetypeid']) && array_key_exists('data', $image)) {
                    $type     = self::getPictureType((int)$image['picturetypeid']);
                    $images[] = [
                        'raw' => $image['data'],
                        'mime' => $image['mime'],
                        'title' => 'ID3 ' . $type
                    ];
                }
            }
        }

        if (isset($id3['id3v2']['PIC'])) {
            // Foreach in case they have more than one
            foreach ($id3['id3v2']['PIC'] as $image) {
                if (isset($image['picturetypeid']) && array_key_exists('data', $image)) {
                    $type     = self::getPictureType((int)$image['picturetypeid']);
                    $images[] = [
                        'raw' => $image['data'],
                        'mime' => $image['image_mime'],
                        'title' => 'ID3 ' . $type
                    ];
                }
            }
        }

        if (isset($id3['flac']['PICTURE'])) {
            // Foreach in case they have more than one
            foreach ($id3['flac']['PICTURE'] as $image) {
                if (isset($image['typeid']) && array_key_exists('data', $image)) {
                    $type     = self::getPictureType((int)$image['typeid']);
                    $images[] = [
                        'raw' => $image['data'],
                        'mime' => $image['image_mime'],
                        'title' => 'ID3 ' . $type
                    ];
                }
            }
        }

        if (isset($id3['comments']['picture'])) {
            // Foreach in case they have more than one
            foreach ($id3['comments']['picture'] as $image) {
                if (isset($image['picturetype']) && array_key_exists('data', $image)) {
                    $images[] = [
                        'raw' => $image['data'],
                        'mime' => $image['image_mime'],
                        'title' => 'ID3 ' . $image['picturetype']
                    ];
                }
                if (isset($image['description']) && array_key_exists('data', $image)) {
                    $images[] = [
                        'raw' => $image['data'],
                        'mime' => $image['image_mime'],
                        'title' => 'ID3 ' . $image['description']
                    ];
                }
            }
        }

        return $images;
    }

    /**
     * Gather tags from files. (rotate through existing images so you don't return a tone of dupes)
     * @param Song|Video $media
     * @param array $data
     * @return array<int, array{raw: string, mime: string, title: string}>
     */
    private function gatherMediaTags(Song|Video $media, array $data): array
    {
        if ($media instanceof Song) {
            $mtype = 'song';
        } else {
            $mtype = 'video';
        }
        $images = self::gatherFileArt((string)$media->file);

        // stop collecting dupes for each album
        $raw_array = [];
        foreach ($data as $image) {
            $raw_array[] = $image['raw'];
        }

        foreach ($images as $image) {
            if (!in_array($image['raw'], $raw_array)) {
                $raw_array[]   = $image['raw'];
                $image[$mtype] = $media->file;
                $data[]        = $image;
            }
        }

        return $data;
    }

    /**
     * Gather tags from single song instead of full album
     * (taken from function gather_song_tags with some changes)
     * @param Art $art
     * @param int $limit
     * @return array
     */
    public function gatherSongTagsSingle(Art $art, int $limit = 5): array
    {
        // get song object directly from id, not by loop through album
        $song = new Song($art->object_id);
        $data = $this->gatherMediaTags($song, []);

        $data = self::sortArtByPriority($data, $art->object_type);

        if ($limit && count($data) >= $limit) {
            $data = array_slice($data, 0, $limit);
        }

        return $data;
    }

    /**
     * Get the type of picture being returned (https://id3.org/id3v2.3.0#Attached_picture)
     * Flac also uses the id3.org specs.
     */
    public static function getPictureType(int $picture_type): string
    {
        return match ($picture_type) {
            1 => '32x32 PNG Icon',
            2 => 'Other Icon',
            3 => 'Front Cover',
            4 => 'Back Cover',
            5 => 'Leaflet',
            6 => 'Media',
            7 => 'Lead Artist',
            8 => 'Artist',
            9 => 'Conductor',
            10 => 'Band',
            11 => 'Composer',
            12 => 'Lyricist',
            13 => 'Recording Studio or Location',
            14 => 'Recording Session',
            15 => 'Performance',
            16 => 'Capture from Movie or Video',
            17 => 'Bright(ly) Colored Fish',
            18 => 'Illustration',
            19 => 'Band Logo',
            20 => 'Publisher Logo',
            default => 'Other',
        };
    }
}
