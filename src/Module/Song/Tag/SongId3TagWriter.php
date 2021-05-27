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
        ?array $data = null,
        ?array $changed = null
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_ID3) === false) {
            return;
        }
        
        global $dic;
        $utilityFactory = $dic->get(UtilityFactoryInterface::class);
 
        $catalog = Catalog::create_from_id($song->catalog);
        if ($catalog->get_type() == 'local') {
            $this->logger->debug(
                sprintf('Writing id3 metadata to file %s', $song->file),
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
            $blackist   = ['totaltracks' ];
            $result     = $vainfo->read_id3();
            $fileformat = $result['fileformat'];
            $song->format();
            if ($fileformat == 'mp3') {
                $songMeta  = $this->getMetadata($song);
                $txxxData  = $result['id3v2']['comments']['text'];
                $id3v2Data = $result['tags']['id3v2'];
                $apics     = $result['id3v2']['APIC'];
                $keys      = array_keys($id3v2Data);
                // Update existing file frames.
                foreach ($txxxData as $key => $value) {
                    $idx = $this->search_txxx($key, $songMeta['text']);
                    if ($idx) {
                        $ndata['text'][] = array('data' => $songMeta['text'][$idx]['data'], 'description' => $key, 'encodingid' => 0);
                    } else {
                        $ndata['text'][] = array('data' => $value, 'description' => $key, 'encodingid' => 0);
                    }
                }
                $keys = array_keys($result['tags']['id3v2']);
                if ($i = array_search('text', $keys)) {
                    unset($keys[$i]);
                }
                foreach ($keys as $key) {
                    if ($songMeta[$key]) {
                        if ($id3v2Data[$key][0] == $songMeta[$key]) {
                            $ndata[$key][0] = $id3v2Data[$key][0];
                        } else {
                            $ndata[$key][0] = $songMeta[$key];
                        }
                    } else {
                        $ndata[$key][0] = $id3v2Data[$key][0];
                    }
                }
            } else {
                $songMeta  = $this->getVorbisMetadata($song);
                $keys      = array_keys($result['tags']['vorbiscomment']);
                $tdata     = $result['tags']['vorbiscomment'];
                $apics     = $result['flac']['PICTURE'];
                foreach ($keys as $key) {
                    if (!is_null($songMeta[$key]) && is_null($tdata[$key])) {
                        $ndata[$key][0] = $songMeta[$key];
                    } elseif (!is_null($songMeta[$key]) && $songMeta[$key] != $tdata[$key][0]) {
                        $ndata[$key][0] = $songMeta[$key];
                    } else {
                        $ndata[$key] = $tdata[$key];
                    }
                }
            }
            $apic_typeid   = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'typeid' : 'picturetypeid';
            $apic_mimetype = ($fileformat == 'flac' || $fileformat == 'ogg') ? 'image_mime' : 'mime';

            $file_has_pics = isset($apics);
            if ($file_has_pics) {
                foreach ($apics as $apic) {
                    $ndata['attached_picture'][] = array('data' => $apic['data'], 'mime' => $apic['mime'],
                        'picturetypeid' => $apic['picturetypeid'], 'description' => $apic['description'], 'encodingid' => $apic['encodingid']);
                }
            }
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_ID3_ART) === true) {
                $art         = new Art($song->album, 'album');
                if ($art->has_db_info()) {
                    $album_image = $art->get(true);
                    $new_pic     = array('data' => $album_image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 3, 'description' => $song->f_album, 'encodingid' => 0);
                    $idx = $art->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;
                    }
                }
                $art = new Art($song->artist, 'artist');
                if ($art->has_db_info()) {
                    $artist_image = $art->get(true);
                    $new_pic      = array('data' => $artist_image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 8, 'description' => $song->f_artist_full, 'encodingid' => 0);
                    $idx = $art->check_for_duplicate($apics, $ndata, $new_pic, $apic_typeid);
                    if (is_null($idx)) {
                        $ndata['attached_picture'][] = $new_pic;                    }
                }
            }
            $vainfo->write_id3($ndata);
        }
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

    private function getVorbisMetadata(
        Song $song
    ): array {
        $song->format(true);
        $meta = [];

        $meta['date']                   = $song->year;
        $meta['playtime_seconds']       = $song->time;
        $meta['title']                  = $song->title;
        $meta['comment']                = $song->comment;
        $meta['album']                  = $song->f_album_full;
        $meta['artist']                 = $song->f_artist_full;
        $meta['albumartist']            = $song->f_albumartist_full;
        $meta['composer']               = $song->composer;
        $meta['publisher']              = $song->f_publisher;
        $meta['track']                  = $song->f_track;
        $meta['discnumber']             = $song->disk;
        $meta['musicbrainz_trackid']    = $song->mbid;
        $meta['musicbrainz_albumid']    = $song->album_mbid;
        $meta['license']                = $song->license;
        $meta['label']                  = $song->_get_ext_info('label');
        $meta['genre']                  = [];

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
        $albumRepo                          =  $dic->get(AlbumRepositoryInterface::class);
        $meta['musicbrainz_albumartistid']  = $albumRepo->getAlbumArtistMbid($album->album_artist);
        $meta['releasetype']                = $album->release_type;
        $meta['releasestatus']              = $album->release_status;
        $meta['barcode']                    = $album->barcode;
        $meta['catalognumber']              = $album->catalog_number;
        $meta['original_year']              = $album->original_year;
        /*
                $meta['LABEL']  = $song->_get_ext_info('label')
                $meta['MUSICBRAINZ_RELEASETRACKID'] = $song->mbid
                $meta['MUSICBRAINZ_ALBUMID'] = $song->album_mbid
                $meta['MUSICBRAINZ_RELEASEGROUPID']  TODO
                $meta['MUSICBRAINZ_TRACKID']   ?
                $meta['LICENSE'] = $song->license
                $meta['CATALOGNUMBER'] = $album->catalog_number
                $meta['BARCODE'] = $album->barcode;
                $meta['MUSICBRAINZ_ALBUMARTISTID'] = $albumRepo->getAlbumArtistMbid
                $meta['ORIGINALDATE']  ?
                $meta['RELEASEDATE']   Doesn't exist in id3v2.3
                $meta['RELEASETYPE'] = $album->release_type;
                $meta['TOTALTRACKS']   ?
                $meta['DATE']          TODO (Doesn't exist in id3v2.3) 

        */
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
        $meta['unique_file_identifier']   = ['data' => $song->mbid, 'ownerid' => "http://musicbrainz.org"];
        $meta['text'][]                   = ['data' => $song->album_mbid, 'description' => 'MusicBrainz Album Id',
                            'encodingid' => 0];

        $meta['genre']         = [];

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
        $albumRepo                      =  $dic->get(AlbumRepositoryInterface::class);
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
