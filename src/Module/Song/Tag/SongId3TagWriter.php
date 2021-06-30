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

namespace Ampache\Module\Song\Tag;

use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Album;
use Ampache\Repository\AlbumRepositoryInterface;

use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\VaInfo;
use Psr\Log\LoggerInterface;

final class SongId3TagWriter implements SongId3TagWriterInterface
{
    private ConfigContainerInterface $configContainer;
    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    /**
     * Write the current song id3 metadata to the file
     */
    public function write(
        Song $song,
        ?Art $art = null,
        ?int $picturetypeid = null
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_ID3) === false) {
            return;
        }
        
        global $dic;
        $utilityFactory = $dic->get(UtilityFactoryInterface::class);
 
        $catalog = Catalog::create_from_id($song->catalog);
        if ($catalog->get_type() == 'local') {
            $this->logger->debug(
                sprintf('Writing metadata to file %s', $song->file),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA) === true) {
                foreach ($song->getMetadata() as $metadata) {
                    $meta[$metadata->getField()->getName()] = $metadata->getData();
                }
            }
            $vainfo = $utilityFactory->createVaInfo(
                $song->file
            );
            
            $ndata      = array();
            $result     = $vainfo->read_id3();
            $fileformat = $result['fileformat'];

            $song->format();
            if ($fileformat == 'mp3') {
                $songMeta  = $this->getMetadata($song);
                $txxxData  = $result['id3v2']['comments']['text'];
                $id3v2Data = $result['tags']['id3v2'];
                $apics     = $result['id3v2']['APIC'];
                // Update existing file frames.
                foreach ($txxxData as $key => $value) {
                    $idx = $this->search_txxx($key, $songMeta['text']);
                    if ($idx) {
                        $ndata['text'][] = array('data' => $songMeta['text'][$idx]['data'], 'description' => $key, 'encodingid' => 0);
                    } else {
                        $ndata['text'][] = array('data' => $value, 'description' => $key, 'encodingid' => 0);
                    }
                }
                unset($id3v2Data['text']);
                foreach ($id3v2Data as $key => $value) {
                    if (isset($songMeta[$key]) && $id3v2Data[$key][0] !== $songMeta[$key]) {
                        $ndata[$key][] = $songMeta[$key];
                    } else {
                        $ndata[$key][] = $id3v2Data[$key][0];
                    }
                }
                
                if (isset($songMeta['unique_file_identifier'])) {
                    $ndata['unique_file_identifier'] = $songMeta['unique_file_identifier'];
                }
                if (isset($songMeta['Popularimeter'])) {
                    $ndata['Popularimeter'] = $songMeta['Popularimeter'];
                }
            } else {
                $songMeta           = $this->getVorbisMetadata($song);
                $vorbiscomments     = $result['tags']['vorbiscomment'];
                $apics              = $result['flac']['PICTURE'];
                /*  Update existing vorbiscomments */
                foreach ($vorbiscomments as $key => $value) {
                    if (isset($songMeta[$key])) {
                        if ($key == 'releasetype' || $key == 'releasestatus') {
                            $ndata[$key] = $songMeta[$key];
                        } else {
                            $ndata[$key][] = $songMeta[$key];
                        }
                    } else {
                        $ndata[$key] = $vorbiscomments[$key];
                    }
                }
                
                /* Insert vorbiscomments that might not be in file.  */
                foreach ($songMeta as $key => $value) {
                    if (!isset($vorbiscomments[$key]) && isset($songMeta[$key])) {
                        if ($key == 'releasetype' || $key == 'releasestatus') {
                            $ndata[$key] = $songMeta[$key];
                        } else {
                            $ndata[$key][] = $songMeta[$key];
                        }
                    }
                }
            }
            $apic_typeid   = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'typeid' : 'picturetypeid';
            $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'image_mime' : 'mime';
            $file_has_pics = isset($apics);
            if ($file_has_pics) {
                foreach ($apics as $apic) {
                    $ndata['attached_picture'][] = array('data' => $apic['data'], 'mime' => $apic[$apic_mimetype],
                        'picturetypeid' => $apic[$apic_typeid], 'description' => $apic['description'], 'encodingid' => $apic['encodingid']);
                }
            }
               
            if (isset($art) == true) {
                $image        = $art->get(true);
                $new_pic      = array('data' => $image, 'mime' => $art->raw_mime,
                    'picturetypeid' => $picturetypeid, 'description' => $song->f_artist_full, 'encodingid' => 0);
                if ($file_has_pics) {
                    $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                } else {
                    $ndata['attached_picture'][] = $new_pic;
                }
            } else {
                $art = new Art($song->artist, 'artist');
                if ($art->has_db_info()) {
                    $image        = $art->get(true);
                    $new_pic      = array('data' => $image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 8, 'description' => $song->f_artist_full, 'encodingid' => 0);
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
                    $image        = $art->get(true);
                    $new_pic      = array('data' => $image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 3, 'description' => $song->f_album, 'encodingid' => 0);
                    if ($file_has_pics) {
                        $idx = $this->check_for_duplicate($apics, $new_pic, $ndata, $apic_typeid);
                        if (is_null($idx)) {
                            $ndata['attached_picture'][] = $new_pic;
                        }
                    } else {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                }
            }
            $vainfo->write_id3($ndata);
        } // catalog type = local
    } //write
    
    private function search_txxx($description, $ndata)
    {
        $i   = 0;
        $cnt = count($ndata);
        for ($i=0; $i < $cnt; $i++) {
            if (strtolower($ndata[$i]['description']) == strtolower($description)) {
                return $i;//Return index
            }
        }

        return null;
    }
    
    public function check_for_duplicate($apics, $new_pic, &$ndata, $apic_typeid)
    {
        $idx = null;
        $cnt = count($apics);
        for ($i=0; $i < $cnt; $i++) {
            if ($new_pic['picturetypeid'] == $apics[$i][$apic_typeid]) {
                $ndata['attached_picture'][$i]['description']       = $new_pic['description'];
                $ndata['attached_picture'][$i]['data']              = $new_pic['data'];
                $ndata['attached_picture'][$i]['mime']              = $new_pic['mime'];
                $ndata['attached_picture'][$i]['picturetypeid']     = $new_pic['picturetypeid'];
                $ndata['attached_picture'][$i]['encodingid']        = $new_pic['encodingid'];
                $idx                                                = $i;
            }
        }

        return $idx;
    }

    private function getVorbisMetadata(
        Song $song
    ): array {
        $song->format(true);
        $meta = [];

        $meta['date']                          = $song->year;
        $meta['title']                         = $song->title;
        $meta['comment']                       = $song->comment;
        $meta['album']                         = $song->f_album_full;
        $meta['artist']                        = $song->f_artist_full;
        $meta['albumartist']                   = $song->f_albumartist_full;
        $meta['composer']                      = $song->composer;
        $meta['label']                         = $song->f_publisher;
        $meta['tracknumber']                   = $song->f_track;
        $meta['discnumber']                    = $song->disk;
        $meta['musicbrainz_releasetrackid']    = $song->mbid;
        $meta['musicbrainz_albumid']           = $song->album_mbid;
        $meta['license']                       = $song->license;
        $res                                   = $song->_get_ext_info('label');
        $meta['label']                         = $res['label'];
        $meta['genre']                         = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);
        
        $album = new Album($song->album);
        $album->format(true);
        global $dic;
        $albumRepo                              = $dic->get(AlbumRepositoryInterface::class);
        $meta['musicbrainz_albumartistid']      = $albumRepo->getAlbumArtistMbid($album->album_artist);
        $meta['musicbrainz_releasegroupid']     = $album->mbid_group;
     
        if (isset($album->release_type)) {
            $release_type                       = explode(',', $album->release_type);
            if (count($release_type) == 2) {
                $release_type[1]        =trim($release_type[1]);
            }
            $meta['releasetype']        = $release_type;
        }

        if (isset($album->release_status)) {
            $release_status                     = explode(',', $album->release_status);
            if (count($release_status) == 2) {
                $release_status[1]              =trim($release_status[1]);
            }
            $meta['releasestatus']              = $release_status;
        }
        $meta['barcode']                        = $album->barcode;
        $meta['catalognumber']                  = $album->catalog_number;
        $meta['original_year']                  = $album->original_year;

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS) === true) {
            $user        = $GLOBALS['user'];
            if (!empty($user->email)) {
                $sql         = "SELECT `rating` FROM `rating` WHERE `user` = ? " . "AND `object_id` = ? AND `object_type` = ?";
                $db_results  = Dba::read($sql, array($user->id, $song->id, 'song'));
                $rating      = 0;
                if ($results = Dba::fetch_assoc($db_results)) {
                    $rating  = $results['rating'];
                }
                $rating         = $rating * (255 / 5);
                $meta['rating'] = (string)$rating;
            } else {
                $this->logger->debug(
                    'User must have an email address on record.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        return $meta;
    }

    /**
     * Get an array of metadata for writing id3 file tags.
     */
    private function getMetadata(
        Song $song
    ): array {
        $meta = [];

        $meta['year']                     = $song->year;
        $meta['time']                     = $song->time;
        $meta['title']                    = $song->title;
        $meta['comment']                  = $song->comment;
        $meta['album']                    = $song->f_album_full;
        $meta['artist']                   = $song->f_artist_full;
        $meta['band']                     = $song->f_albumartist_full;
        $meta['composer']                 = $song->composer;
        $meta['publisher']                = $song->f_publisher;
        $meta['track_number']             = $song->f_track;
        $meta['part_of_a_set']            = $song->disk;
        if (isset($song->mbid)) {
            $meta['unique_file_identifier']   = ['data' => $song->mbid, 'ownerid' => "http://musicbrainz.org"];
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS) === true) {
            $user        = $GLOBALS['user'];
            $sql         = "SELECT `rating` FROM `rating` WHERE `user` = ? " . "AND `object_id` = ? AND `object_type` = ?";
            $db_results  = Dba::read($sql, array($user->id, $song->id, 'song'));
            $rating      = 0;
            if ($results = Dba::fetch_assoc($db_results)) {
                $rating  = $results['rating'];
            }
            $rating = $rating * (255 / 5);
            if (!empty($user->email)) {
                $meta['Popularimeter'] = ["email" => $user->email, "rating" => $rating,
                                         "data" => $song->played];
            } else {
                $this->logger->debug(
                    'User must have an email address on record.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }

        $meta['text'][] = ['data' => $song->album_mbid, 'description' => 'MusicBrainz Album Id',
                            'encodingid' => 0];

        $meta['genre']  = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);
        
        $album = new Album($song->album);
        $album->format(true);
        global $dic;
        $albumRepo                      = $dic->get(AlbumRepositoryInterface::class);
        $meta['original_year']          = $album->original_year;  //TORY

        $meta['text'][]     = ['data' => $albumRepo->getAlbumArtistMbid($album->album_artist), 'description' => 'MusicBrainz Album Artist Id',
                                'encodingid' => 0];
        $meta['text'][]     = ['data' => $album->release_status, 'description' => 'MusicBrainz Album Status',
                                'encodingid' => 0];
        $meta['text'][]     = ['data' => $album->release_type, 'description' => 'MusicBrainz Album Type',
                                'encodingid' => 0];
        $meta['text'][]     = ['data' => $album->barcode, 'description' => 'BARCODE', 'encodingid' => 0];

        $meta['text'][]     = ['data' => $album->catalog_number, 'description' => 'CATALOGNUMBER', 'encodingid' => 0];

        return $meta;
    }
}
