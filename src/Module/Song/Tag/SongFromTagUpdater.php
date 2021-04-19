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

declare(strict_types=0);

namespace Ampache\Module\Song\Tag;

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\DataMigratorInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\TagRepositoryInterface;

final class SongFromTagUpdater implements SongFromTagUpdaterInterface
{
    private DataMigratorInterface $dataMigrator;

    private LabelRepositoryInterface $labelRepository;

    private LicenseRepositoryInterface $licenseRepository;

    private TagRepositoryInterface $tagRepository;

    public function __construct(
        DataMigratorInterface $dataMigrator,
        LabelRepositoryInterface $labelRepository,
        LicenseRepositoryInterface $licenseRepository,
        TagRepositoryInterface $tagRepository
    ) {
        $this->dataMigrator      = $dataMigrator;
        $this->labelRepository   = $labelRepository;
        $this->licenseRepository = $licenseRepository;
        $this->tagRepository     = $tagRepository;
    }

    /**
     * Updates the song info based on tags; this is called from a bunch of
     * different places and passes in a full fledged song object
     * FIXME: This is an ugly mess, this really needs to be consolidated and
     * cleaned up.
     * @param array $results
     * @param Song $song
     * @return array
     */
    public function update(
        array $results,
        Song $song
    ): array {
        // info for the song table. This is all the primary file data that is song related
        $new_song       = new Song();
        $new_song->file = $results['file'];
        $new_song->year = (strlen((string)$results['year']) > 4) ? (int)substr($results['year'], -4,
            4) : (int)($results['year']);
        $new_song->title   = Catalog::check_length(Catalog::check_title($results['title'], $new_song->file));
        $new_song->bitrate = $results['bitrate'];
        $new_song->rate    = $results['rate'];
        $new_song->mode    = ($results['mode'] == 'cbr') ? 'cbr' : 'vbr';
        $new_song->size    = $results['size'];
        $new_song->time    = (strlen((string)$results['time']) > 5) ? (int)substr($results['time'], -5,
            5) : (int)($results['time']);
        if ($new_song->time < 0) {
            // fall back to last time if you fail to scan correctly
            $new_song->time = $song->time;
        }
        $new_song->track    = Catalog::check_track((string)$results['track']);
        $new_song->mbid     = $results['mb_trackid'];
        $new_song->composer = Catalog::check_length($results['composer']);
        $new_song->mime     = $results['mime'];

        // info for the song_data table. used in Song::update_song
        $new_song->comment     = $results['comment'];
        $new_song->lyrics      = str_replace(
            ["\r\n", "\r", "\n"],
            '<br />',
            strip_tags($results['lyrics'])
        );
        if (isset($results['license'])) {
            $licenseName = (string) $results['license'];
            $licenseId   = $this->licenseRepository->find($licenseName);

            $new_song->license = $licenseId === 0 ? $this->licenseRepository->create($licenseName, '', '') : $licenseId;
        } else {
            $new_song->license = null;
        }
        $new_song->label = isset($results['publisher']) ? Catalog::check_length($results['publisher'], 128) : null;
        if ($song->label && AmpConfig::get('label')) {
            // create the label if missing
            foreach (array_map('trim', explode(';', $new_song->label)) as $label_name) {
                Label::helper($label_name);
            }
        }
        $new_song->language              = Catalog::check_length($results['language'], 128);
        $new_song->replaygain_track_gain = !is_null($results['replaygain_track_gain']) ? (float) $results['replaygain_track_gain'] : null;
        $new_song->replaygain_track_peak = !is_null($results['replaygain_track_peak']) ? (float) $results['replaygain_track_peak'] : null;
        $new_song->replaygain_album_gain = !is_null($results['replaygain_album_gain']) ? (float) $results['replaygain_album_gain'] : null;
        $new_song->replaygain_album_peak = !is_null($results['replaygain_album_peak']) ? (float) $results['replaygain_album_peak'] : null;
        $new_song->r128_track_gain       = !is_null($results['r128_track_gain']) ? (int) $results['r128_track_gain'] : null;
        $new_song->r128_album_gain       = !is_null($results['r128_album_gain']) ? (int) $results['r128_album_gain'] : null;

        // genre is used in the tag and tag_map tables
        $new_song->tags = $results['genre'];
        $tags           = Tag::get_object_tags('song', $song->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $song->tags[] = $tag['name'];
            }
        }
        // info for the artist table.
        $artist           = Catalog::check_length($results['artist']);
        $artist_mbid      = $results['mb_artistid'];
        $albumartist_mbid = $results['mb_albumartistid'];
        // info for the album table.
        $album      = Catalog::check_length($results['album']);
        $album_mbid = $results['mb_albumid'];
        $disk       = $results['disk'];
        // year is also included in album
        $album_mbid_group = $results['mb_albumid_group'];
        $release_type     = Catalog::check_length($results['release_type'], 32);
        $albumartist      = Catalog::check_length($results['albumartist'] ?: $results['band']);
        $albumartist      = $albumartist ?: null;
        $original_year    = $results['original_year'];
        $barcode          = Catalog::check_length($results['barcode'], 64);
        $catalog_number   = Catalog::check_length($results['catalog_number'], 64);

        // check whether this artist exists (and the album_artist)
        $new_song->artist = Artist::check($artist, $artist_mbid);
        if ($albumartist) {
            $new_song->albumartist = Artist::check($albumartist, $albumartist_mbid);
            if (!$new_song->albumartist) {
                $new_song->albumartist = $song->albumartist;
            }
        }
        if (!$new_song->artist) {
            $new_song->artist = $song->artist;
        }

        // check whether this album exists
        $new_song->album = Album::check($album, $new_song->year, $disk, $album_mbid, $album_mbid_group, $new_song->albumartist, $release_type, $original_year, $barcode, $catalog_number);
        if (!$new_song->album) {
            $new_song->album = $song->album;
        }
        // set `song`.`update_time` when artist or album details change
        $update_time = time();

        if (
            $this->dataMigrator->migrate('artist', $song->artist, $new_song->artist) ||
            $this->dataMigrator->migrate('album', $song->album, $new_song->album)
        ) {
            Song::update_utime($song->id, $update_time);
        }

        if ($artist_mbid) {
            $new_song->artist_mbid = $artist_mbid;
        }
        if ($album_mbid) {
            $new_song->album_mbid = $album_mbid;
        }
        if ($albumartist_mbid) {
            $new_song->albumartist_mbid = $albumartist_mbid;
        }

        /* Since we're doing a full compare make sure we fill the extended information */
        $song->fill_ext_info();

        if (Song::isCustomMetadataEnabled()) {
            $ctags = Catalog::get_clean_metadata($song, $results);
            if (method_exists($song, 'updateOrInsertMetadata') && $song::isCustomMetadataEnabled()) {
                $ctags = array_diff_key($ctags, array_flip($song->getDisabledMetadataFields()));
                foreach ($ctags as $tag => $value) {
                    $field = $song->getField($tag);
                    $song->updateOrInsertMetadata($field, $value);
                }
            }
        }

        // Duplicate arts if required
        if (($song->artist && $new_song->artist) && $song->artist != $new_song->artist) {
            if (!Art::has_db($new_song->artist, 'artist')) {
                Art::duplicate('artist', $song->artist, $new_song->artist);
            }
        }
        if (($song->albumartist && $new_song->albumartist) && $song->albumartist != $new_song->albumartist) {
            if (!Art::has_db($new_song->albumartist, 'artist')) {
                Art::duplicate('artist', $song->albumartist, $new_song->albumartist);
            }
        }
        if (($song->album && $new_song->album) && $song->album != $new_song->album) {
            if (!Art::has_db($new_song->album, 'album')) {
                Art::duplicate('album', $song->album, $new_song->album);
            }
        }
        if ($song->label && AmpConfig::get('label')) {
            foreach (array_map('trim', explode(';', $song->label)) as $label_name) {
                $label_id = Label::helper($label_name)
                    ?: $this->labelRepository->lookup($label_name);
                if ($label_id > 0) {
                    $label   = new Label($label_id);
                    $artists = $this->labelRepository->getArtists($label->getId());
                    if (!in_array($song->artist, $artists)) {
                        debug_event(__CLASS__, "$song->artist: adding association to $label->name", 4);
                        $this->labelRepository->addArtistAssoc($label->id, $song->artist);
                    }
                }
            }
        }

        $info = Song::compare_song_information($song, $new_song);
        if ($info['change']) {
            debug_event(self::class, "$song->file : differences found, updating database", 4);

            // Update song_data table
            Song::update_song($song->id, $new_song);

            if (!empty($new_song->tags) && $song->tags != $new_song->tags) {
                Tag::update_tag_list(implode(',', $new_song->tags), 'song', $song->id, true);
                $tags = $this->tagRepository->getSongTags('album', $song->album);
                Tag::update_tag_list(implode(',', $tags), 'album', $song->album, true);
                $tags = $this->tagRepository->getSongTags('artist', $song->artist);
                Tag::update_tag_list(implode(',', $tags), 'artist', $song->artist, true);
            }
            if ($song->license != $new_song->license) {
                Song::update_license($new_song->license, $song->id);
            }
            // Refine our reference
            //$song = $new_song;
        } else {
            debug_event(self::class, "$song->file : no differences found", 5);
        }

        // If song rating tag exists and is well formed (array user=>rating), update it
        if ($song->id && array_key_exists('rating', $results) && is_array($results['rating'])) {
            // For each user's ratings, call the function
            foreach ($results['rating'] as $user => $rating) {
                debug_event(self::class, "Updating rating for Song " . $song->id . " to $rating for user $user", 5);
                $o_rating = new Rating($song->id, 'song');
                $o_rating->set_rating($rating, $user);
            }
        }

        return $info;
    }
}
