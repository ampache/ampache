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
            
            $result = $vainfo->read_id3();
            if ($result['fileformat'] == 'mp3') {
                $tdata = $result['tags']['id3v2'];
                $apics = $result['id3v2']['APIC'];
                $meta  = $this->getMetadata($song);
            } else {
                $tdata = $result['tags']['vorbiscomment'];
                $apics = $result['flac']['PICTURE'];
                $meta  = $this->getVorbisMetadata($song);
            }
            $ndata = $vainfo->prepare_metadata_for_writing($tdata);

            if (isset($changed)) {
                foreach ($changed as $key => $value) {
                    switch ($value) {
                        case 'artist':
                        case 'artist_name':
                            $ndata['artist'][0] = $song->f_artist;
                            break;
                        case 'album':
                        case 'album_name':
                            $ndata['album'][0] = $song->f_album;
                            break;
                        case 'track':
                            $ndata['track_number'][0] = $data['track'];
                            break;
                        case 'label':
                            $ndata['publisher'][0] = $data['label'];
                            break;
                        case 'edit_tags':
                            $ndata['genre'][0] = $data['edit_tags'];
                            break;
                        default:
                            $ndata[$value][0] = $data[$value];
                            break;
                    }
                }
            } else {
                // Fill in existing tag frames
                foreach ($meta as $key => $value) {
                    if ($key != 'text' && $key != 'totaltracks') {
                        $ndata[$key][0] = $meta[$key] ?:'';
                    }
                }
            }
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WRITE_ID3_ART) === true) {
                $art         = new Art($song->album, 'album');
                $album_image = $art->get(true);
                if ($album_image != '') {
                    $ndata['attached_picture'][] = array('data' => $album_image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 3, 'description' => $song->f_album);
                }
                $art = new Art($song->artist, 'artist');
                if ($art->has_db_info()) {
                    $artist_image                = $art->get(true);
                    $ndata['attached_picture'][] = array('data' => $artist_image, 'mime' => $art->raw_mime,
                        'picturetypeid' => 8, 'description' => $song->f_artist);
                }
            } else {    // rewrite original images
                if (!is_null($apics)) {
                    foreach ($apics as $apic) {
                        $ndata['attached_picture'][] = $apic;
                    }
                }
            }
            $vainfo->write_id3($ndata);
        }
    }

    private function getVorbisMetadata(
        Song $song
    ): array {
        $meta = [];

        $meta['date']        = $song->year;
        $meta['time']        = $song->time;
        $meta['title']       = $song->title;
        $meta['comment']     = $song->comment;
        $meta['album']       = $song->f_album_full;
        $meta['artist']      = $song->f_artist_full;
        $meta['albumartist'] = $song->f_albumartist_full;
        $meta['composer']    = $song->composer;
        $meta['publisher']   = $song->f_publisher;
        $meta['track']       = $song->f_track;
        $meta['discnumber']  = $song->disk;
        $meta['genre']       = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);

        return $meta;
    }

    /**
     * Get an array of metadata for writing id3 file tags.
     */
    private function getMetadata(
        Song $song
    ): array {
        $meta = [];

        $meta['year']          = $song->year;
        $meta['time']          = $song->time;
        $meta['title']         = $song->title;
        $meta['comment']       = $song->comment;
        $meta['album']         = $song->f_album_full;
        $meta['artist']        = $song->f_artist_full;
        $meta['band']          = $song->f_albumartist_full;
        $meta['composer']      = $song->composer;
        $meta['publisher']     = $song->f_publisher;
        $meta['track_number']  = $song->f_track;
        $meta['part_of_a_set'] = $song->disk;
        $meta['genre']         = [];

        if (!empty($song->tags)) {
            foreach ($song->tags as $tag) {
                if (!in_array($tag['name'], $meta['genre'])) {
                    $meta['genre'][] = $tag['name'];
                }
            }
        }
        $meta['genre'] = implode(',', $meta['genre']);

        return $meta;
    }
}
