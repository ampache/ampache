<?php

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

namespace Ampache\Module\Song\Tag;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UtilityFactoryInterface;
use Psr\Log\LoggerInterface;

final class SongTagWriter implements SongTagWriterInterface
{
    private ConfigContainerInterface $configContainer;

    private UtilityFactoryInterface $utilityFactory;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UtilityFactoryInterface $utilityFactory,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->utilityFactory  = $utilityFactory;
        $this->logger          = $logger;
    }

    /**
     * Write the current song id3 metadata to the file
     */
    public function write(
        Song $song
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_TAGS) === false) {
            return;
        }

        $catalog = Catalog::create_from_id($song->getCatalogId());
        if ($catalog === null) {
            return;
        }
        if ($catalog->get_type() == 'local') {
            $this->logger->debug(
                sprintf('Writing metadata to file %s', $song->file),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $ndata = array();
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA) === true) {
                foreach ($song->getMetadata() as $metadata) {
                    $field = $metadata->getField();

                    if ($field !== null) {
                        $ndata[$field->getName()] = $metadata->getData();
                    }
                }
            }
            $vainfo = $this->utilityFactory->createVaInfo(
                (string) $song->file
            );

            $result     = $vainfo->read_id3();
            $fileformat = $result['fileformat'];

            $song->format();
            if ($fileformat == 'mp3') {
                $songMeta  = $this->getId3Metadata($song);
                $txxxData  = $result['id3v2']['comments']['text'] ?? array();
                $id3v2Data = $result['tags']['id3v2'] ?? array();
                $apics     = $result['id3v2']['APIC'] ?? null;
                // Update existing file frames.
                if (!empty($txxxData)) {
                    foreach ($txxxData as $key => $value) {
                        $idx = $this->search_txxx($key, $songMeta['text']);
                        if ($idx) {
                            $ndata['text'][] = array(
                                'data' => $songMeta['text'][$idx]['data'],
                                'description' => $key,
                                'encodingid' => 0
                            );
                        } else {
                            $ndata['text'][] = array(
                                'data' => $value,
                                'description' => $key,
                                'encodingid' => 0
                            );
                        }
                    }
                } else {
                    // Assumes file originally had no TXXX frames
                    $metatext = $songMeta['text'];
                    if (!empty($metatext)) {
                        foreach ($metatext as $key => $value) {
                            $ndata['text'][$key] = $value;
                        }
                    }
                }
                if (!empty($id3v2Data)) {
                    unset($id3v2Data['text']);
                    foreach ($id3v2Data as $key => $value) {
                        if (isset($songMeta[$key]) && $value[0] !== $songMeta[$key]) {
                            $ndata[$key][] = $songMeta[$key];
                        } else {
                            $ndata[$key][] = $value[0];
                        }
                    }
                } else {
                    unset($songMeta['text']);
                    foreach ($songMeta as $key => $value) {
                        $ndata[$key][] = $songMeta;
                    }
                }
                if (isset($songMeta['unique_file_identifier'])) {
                    $ndata['unique_file_identifier'] = $songMeta['unique_file_identifier'];
                }
                if (isset($songMeta['Popularimeter'])) {
                    $ndata['Popularimeter'] = $songMeta['Popularimeter'];
                }
            } else {
                $songMeta       = $this->getVorbisMetadata($song);
                $vorbiscomments = $result['tags']['vorbiscomment'] ?? array();
                $apics          = $result['flac']['PICTURE'] ?? null;
                //  Update existing vorbiscomments
                if (!empty($vorbiscomments)) {
                    foreach ($vorbiscomments as $key => $value) {
                        if (isset($songMeta[$key])) {
                            if ($key == 'releasetype' || $key == 'releasestatus') {
                                $ndata[$key] = $songMeta[$key];
                            } else {
                                $ndata[$key][] = $songMeta[$key];
                            }
                        } else {
                            $ndata[$key] = $value;
                        }
                    }
                }
                // Insert vorbiscomments that might not be in file.
                foreach ($songMeta as $key => $value) {
                    if (!isset($vorbiscomments[$key]) && isset($value)) {
                        if ($key == 'releasetype' || $key == 'releasestatus') {
                            $ndata[$key] = $value;
                        } else {
                            $ndata[$key][] = $value;
                        }
                    }
                }
            }
            $apic_typeid = ($fileformat == 'flac' || $fileformat == 'ogg')
                ? 'typeid'
                : 'picturetypeid';
            $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg')
                ? 'image_mime'
                : 'mime';
            $file_has_pics = isset($apics) && is_array($apics);
            if ($file_has_pics) {
                foreach ($apics as $apic) {
                    $ndata['attached_picture'][] = array(
                        'data' => $apic['data'],
                        'mime' => $apic[$apic_mimetype],
                        'picturetypeid' => $apic[$apic_typeid],
                        'description' => $apic['description'],
                        'encodingid' => $apic['encodingid']
                    );
                }
            }

            $art = new Art($song->artist, 'artist');
            if ($art->has_db_info()) {
                $image   = $art->get(true);
                $new_pic = array(
                    'data' => $image,
                    'mime' => $art->raw_mime,
                    'picturetypeid' => 8,
                    'description' => $song->get_artist_fullname(),
                    'encodingid' => 0
                );
                if ($file_has_pics) {
                    $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                } else {
                    $ndata['attached_picture'][] = $new_pic;
                }
            }
            $art = new Art($song->album, 'album');
            if ($art->has_db_info()) {
                $image   = $art->get(true);
                $new_pic = array(
                    'data' => $image,
                    'mime' => $art->raw_mime,
                    'picturetypeid' => 3,
                    'description' => $song->f_album,
                    'encodingid' => 0
                );
                if ($file_has_pics) {
                    $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                } else {
                    $ndata['attached_picture'][] = $new_pic;
                }
            }
            $vainfo->write_id3($ndata);
        } // catalog type = local
    }

    /**
     * Write the song rating to the file and include existing tags
     */
    public function writeRating(
        Song $song,
        User $user,
        Rating $rating
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_TAGS) === false) {
            return;
        }

        $catalog = Catalog::create_from_id($song->getCatalogId());
        if ($catalog === null) {
            return;
        }
        if ($catalog->get_type() == 'local') {
            $this->logger->debug(
                sprintf('Writing rating to file %s', $song->file),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $vainfo = $this->utilityFactory->createVaInfo(
                (string) $song->file
            );

            $ndata      = array();
            $result     = $vainfo->read_id3();
            $fileformat = $result['fileformat'];
            $my_rating  = $rating->get_user_rating($user->id);

            if ($fileformat == 'mp3') {
                $txxxData  = $result['id3v2']['comments']['text'] ?? array();
                $id3v2Data = $result['tags']['id3v2'] ?? array();
                $apics     = $result['id3v2']['APIC'] ?? null;
                // Update existing file frames.
                if (!empty($txxxData)) {
                    foreach ($txxxData as $key => $value) {
                        $ndata['text'][] = array(
                            'data' => $value,
                            'description' => $key,
                            'encodingid' => 0
                        );
                    }
                }
                if (!empty($id3v2Data)) {
                    unset($id3v2Data['text']);
                    foreach ($id3v2Data as $key => $value) {
                        $ndata[$key][] = $value[0];
                    }
                }
                if (!empty($user->email)) {
                    $ndata['Popularimeter'] = [
                        "email" => $user->email,
                        "rating" => ($my_rating > 0) ? $my_rating * (255 / 5) : 0,
                        "data" => $song->get_totalcount()
                    ];
                    $this->logger->debug(
                        print_r($ndata['Popularimeter'], true),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                } else {
                    $this->logger->debug(
                        'Rating user must have an email address on record.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            } else {
                $vorbiscomments = $result['tags']['vorbiscomment'] ?? array();
                $apics          = $result['flac']['PICTURE'] ?? null;
                if (!empty($vorbiscomments)) {
                    // Fill existing tags
                    foreach ($vorbiscomments as $key => $value) {
                        $ndata[$key] = $value;
                    }
                }
                if (!empty($user->email)) {
                    // set a rating and per-user rating
                    $tag_rating                      = array(($my_rating > 0) ? $my_rating * (100 / 5) : 0);
                    $ndata['rating']                 = $tag_rating;
                    $ndata['rating:' . $user->email] = $tag_rating;
                } else {
                    $this->logger->debug(
                        'Rating user must have an email address on record.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
            $apic_typeid = ($fileformat == 'flac' || $fileformat == 'ogg')
                ? 'typeid'
                : 'picturetypeid';
            $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg')
                ? 'image_mime'
                : 'mime';
            $file_has_pics = isset($apics) && is_array($apics);
            if ($file_has_pics) {
                foreach ($apics as $apic) {
                    $ndata['attached_picture'][] = array(
                        'data' => $apic['data'],
                        'picturetypeid' => $apic[$apic_typeid],
                        'description' => $apic['description'] ?? '',
                        'mime' => $apic[$apic_mimetype],
                        'encodingid' => $apic['encodingid'] ?? 0
                    );
                }
            }
            $art = new Art($song->artist, 'artist');
            if ($art->has_db_info()) {
                $image   = $art->get(true);
                $new_pic = array(
                    'data' => $image,
                    'picturetypeid' => 8,
                    'description' => $song->get_artist_fullname(),
                    'mime' => $art->raw_mime,
                    'encodingid' => 0
                );
                if ($file_has_pics) {
                    $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                }
            }
            $art = new Art($song->album, 'album');
            if ($art->has_db_info()) {
                $image   = $art->get(true);
                $new_pic = array(
                    'data' => $image,
                    'picturetypeid' => 3,
                    'description' => $song->f_album,
                    'mime' => $art->raw_mime,
                    'encodingid' => 0
                );
                if ($file_has_pics) {
                    $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                }
            }
            $vainfo->write_id3($ndata);
        } // catalog type = local
    }

    /**
     * @param int|string $description
     * @param $ndata
     * @return int|null
     */
    private function search_txxx($description, $ndata): ?int
    {
        $cnt = count($ndata);
        for ($i = 0; $i < $cnt; $i++) {
            if (strtolower($ndata[$i]['description']) == strtolower((string)$description)) {
                return $i;
            }
        }

        return null;
    }

    public function check_for_duplicate($apics, $new_pic, &$ndata, $apic_typeid): ?int
    {
        $idx = null;
        $cnt = count($apics);
        for ($i = 0; $i < $cnt; $i++) {
            if ($new_pic['picturetypeid'] == $apics[$i][$apic_typeid]) {
                $ndata['attached_picture'][$i]['description']   = $new_pic['description'];
                $ndata['attached_picture'][$i]['data']          = $new_pic['data'];
                $ndata['attached_picture'][$i]['mime']          = $new_pic['mime'];
                $ndata['attached_picture'][$i]['picturetypeid'] = $new_pic['picturetypeid'];
                $ndata['attached_picture'][$i]['encodingid']    = $new_pic['encodingid'];
                $idx                                            = $i;
            }
        }

        return $idx;
    }

    private function getVorbisMetadata(
        Song $song
    ): array {
        $song->format();
        $meta = [];

        $meta['date']                = $song->year;
        $meta['title']               = $song->title;
        $meta['comment']             = $song->comment;
        $meta['album']               = $song->f_album_full;
        $meta['artist']              = $song->get_artist_fullname();
        $meta['albumartist']         = $song->f_albumartist_full;
        $meta['composer']            = $song->composer;
        $meta['label']               = $song->f_publisher;
        $meta['tracknumber']         = $song->f_track;
        $meta['discnumber']          = $song->disk;
        $meta['musicbrainz_trackid'] = $song->mbid;
        $meta['musicbrainz_albumid'] = $song->album_mbid;
        $meta['license']             = $song->license;
        $meta['genre']               = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                $meta['genre'][] = $tag['name'];
            }
        }
        $meta['genre'] = implode(', ', $meta['genre']);

        $album = new Album($song->album);
        $album->format();

        $meta['musicbrainz_albumartistid']  = $song->albumartist_mbid;
        $meta['musicbrainz_releasegroupid'] = $album->mbid_group;

        if (isset($album->release_type)) {
            $release_type = explode(',', $album->release_type);
            if (count($release_type) == 2) {
                $release_type[1] = trim($release_type[1]);
            }
            $meta['releasetype'] = $release_type;
        }

        if (isset($album->release_status)) {
            $release_status = explode(',', $album->release_status);
            if (count($release_status) == 2) {
                $release_status[1] = trim($release_status[1]);
            }
            $meta['releasestatus'] = $release_status;
        }
        $meta['barcode']       = $album->barcode;
        $meta['catalognumber'] = $album->catalog_number;
        $meta['original_year'] = $album->original_year;

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS)) {
            $user      = Core::get_global('user');
            $rating    = new Rating($song->id, 'song');
            $my_rating = $rating->get_user_rating($user->id);
            if (!empty($user->email)) {
                $meta['rating:' . $user->email] = array(($my_rating > 0) ? $my_rating * (100 / 5) : 0);
            } else {
                $this->logger->debug(
                    'Rating user must have an email address on record.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        return $meta;
    }

    /**
     * Get an array of metadata for writing id3 file tags.
     */
    private function getId3Metadata(
        Song $song
    ): array {
        $meta = [];

        $meta['year']          = $song->year;
        $meta['time']          = $song->time;
        $meta['title']         = $song->title;
        $meta['comment']       = $song->comment;
        $meta['album']         = $song->get_album_fullname();
        $meta['artist']        = $song->get_artist_fullname();
        $meta['band']          = $song->f_albumartist_full;
        $meta['composer']      = $song->composer;
        $meta['publisher']     = $song->f_publisher;
        $meta['track_number']  = $song->f_track;
        $meta['part_of_a_set'] = $song->disk;
        if (isset($song->mbid)) {
            $meta['unique_file_identifier'] = [
                'data' => $song->mbid,
                'ownerid' => "http://musicbrainz.org"
            ];
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS)) {
            $user      = Core::get_global('user');
            $rating    = new Rating($song->id, 'song');
            $my_rating = $rating->get_user_rating($user->id);
            if (!empty($user->email)) {
                $meta['Popularimeter'] = [
                    "email" => $user->email,
                    "rating" => ($my_rating > 0) ? $my_rating * (255 / 5) : 0,
                    "data" => $song->get_totalcount()
                ];
            } else {
                $this->logger->debug(
                    'Rating user must have an email address on record.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        $meta['genre'] = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                $meta['genre'][] = $tag['name'];
            }
        }
        $meta['genre'] = implode(', ', $meta['genre']);

        $album = new Album($song->album);
        $album->format();
        $meta['original_year'] = $album->original_year;  //TORY

        $meta['text'] = array();
        if ($song->album_mbid) {
            $meta['text'][] = [
                'data' => $song->album_mbid,
                'description' => 'MusicBrainz Album Id',
                'encodingid' => 0
            ];
        }
        if ($song->albumartist_mbid) {
            $meta['text'][] = [
                'data' => $song->albumartist_mbid,
                'description' => 'MusicBrainz Album Artist Id',
                'encodingid' => 0
            ];
        }
        if ($song->albumartist_mbid) {
            $meta['text'][] = [
                'data' => $song->albumartist_mbid,
                'description' => 'MusicBrainz Album Artist Id',
                'encodingid' => 0
            ];
        }
        if ($album->release_status) {
            $meta['text'][] = [
                'data' => $album->release_status,
                'description' => 'MusicBrainz Album Status',
                'encodingid' => 0];
        }
        if ($album->release_type) {
            $meta['text'][] = [
                'data' => $album->release_type,
                'description' => 'MusicBrainz Album Type',
                'encodingid' => 0
            ];
        }
        if ($album->barcode) {
            $meta['text'][] = [
                'data' => $album->barcode,
                'description' => 'BARCODE',
                'encodingid' => 0
            ];
        }
        if ($album->catalog_number) {
            $meta['text'][] = [
                'data' => $album->catalog_number,
                'description' => 'CATALOGNUMBER',
                'encodingid' => 0
            ];
        }

        return $meta;
    }
}
