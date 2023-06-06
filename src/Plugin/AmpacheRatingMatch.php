<?php
/*
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

declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Module\Song\Tag\SongTagWriterInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\System\Dba;

class AmpacheRatingMatch
{
    public $name        = 'RatingMatch';
    public $categories  = 'scrobbling';
    public $description = 'Raise the album and artist rating to match the highest song rating';
    public $version     = '000004';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $min_stars;
    private $match_flags;
    private $user;
    private $star1_rule;
    private $star2_rule;
    private $star3_rule;
    private $star4_rule;
    private $star5_rule;
    private $flag_rule;
    private $write_tags;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description   = T_('Raise the album and artist rating to match the highest song rating');

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        // Check and see if it's already installed (they've just hit refresh, those dorks)
        if (Preference::exists('ratingmatch_stars')) {
            return false;
        }

        // version 1
        Preference::insert('ratingmatch_stars', T_('Minimum star rating to match'), 0, 25, 'integer', 'plugins', $this->name);
        // version 2
        Preference::insert('ratingmatch_flags', T_('When you love a track, flag the album and artist'), 0, 25, 'boolean', 'plugins', $this->name);
        // version 3
        Preference::insert('ratingmatch_star1_rule', T_('Match rule for 1 Star ($play,$skip)'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('ratingmatch_star2_rule', T_('Match rule for 2 Stars'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('ratingmatch_star3_rule', T_('Match rule for 3 Stars'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('ratingmatch_star4_rule', T_('Match rule for 4 Stars'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('ratingmatch_star5_rule', T_('Match rule for 5 Stars'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('ratingmatch_flag_rule', T_('Match rule for Flags'), '', 25, 'string', 'plugins', $this->name);
        // version 4
        Preference::insert('ratingmatch_write_tags', T_('Save ratings to file tags when changed'), '0', 25, 'boolean', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('ratingmatch_stars');
        Preference::delete('ratingmatch_flags');
        Preference::delete('ratingmatch_star1_rule');
        Preference::delete('ratingmatch_star2_rule');
        Preference::delete('ratingmatch_star3_rule');
        Preference::delete('ratingmatch_star4_rule');
        Preference::delete('ratingmatch_star5_rule');
        Preference::delete('ratingmatch_flag_rule');
        Preference::delete('ratingmatch_write_tags');
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }
        if ($from_version < 2) {
            Preference::insert('ratingmatch_flags', T_('When you love a track, flag the album and artist'), 0, 25, 'boolean', 'plugins', $this->name);
        }
        if ($from_version < 3) {
            Preference::insert('ratingmatch_star1_rule', T_('Match rule for 1 Star ($play,$skip)'), '', 25, 'string', 'plugins', $this->name);
            Preference::insert('ratingmatch_star2_rule', T_('Match rule for 2 Stars'), '', 25, 'string', 'plugins', $this->name);
            Preference::insert('ratingmatch_star3_rule', T_('Match rule for 3 Stars'), '', 25, 'string', 'plugins', $this->name);
            Preference::insert('ratingmatch_star4_rule', T_('Match rule for 4 Stars'), '', 25, 'string', 'plugins', $this->name);
            Preference::insert('ratingmatch_star5_rule', T_('Match rule for 5 Stars'), '', 25, 'string', 'plugins', $this->name);
            Preference::insert('ratingmatch_flag_rule', T_('Match rule for Flags'), '', 25, 'string', 'plugins', $this->name);
        }
        if ($from_version < 4) {
            Preference::insert('ratingmatch_write_tags', T_('Save ratings to file tags when changed'), '0', 25, 'boolean', 'plugins', $this->name);
        }

        return true;
    } // upgrade

    /**
     * save_rating
     * Rate an artist and album after rating the song
     * @param Rating $rating
     * @param integer $new_rating
     */
    public function save_rating($rating, $new_rating)
    {
        if ($this->min_stars > 0 && $new_rating >= $this->min_stars) {
            if ($rating->type == 'song') {
                $song = new Song($rating->id);
                // rate all the song artists (If there are more than one)
                foreach (Song::get_parent_array($song->id) as $artist_id) {
                    $rArtist       = new Rating($artist_id, 'artist');
                    $rating_artist = $rArtist->get_user_rating($this->user->id);
                    if ($rating_artist < $new_rating) {
                        $rArtist->set_rating($new_rating, $this->user->id);
                    }
                }
                $rAlbum       = new Rating($song->album, 'album');
                $rating_album = $rAlbum->get_user_rating($this->user->id);
                if ($rating_album < $new_rating) {
                    $rAlbum->set_rating($new_rating, $this->user->id);
                }
            }
            if ($rating->type == 'album') {
                $album        = new Album($rating->id);
                $rAlbum       = new Rating($rating->id, 'album');
                $rating_album = $rAlbum->get_user_rating($this->user->id);
                if ($rating_album < $new_rating) {
                    $rAlbum->set_rating($new_rating, $this->user->id);
                }
                // rate all the album artists (If there are more than one)
                foreach (Album::get_parent_array($album->id, $album->album_artist) as $artist_id) {
                    $rArtist       = new Rating($artist_id, 'artist');
                    $rating_artist = $rArtist->get_user_rating($this->user->id);
                    if ($rating_artist <= $new_rating) {
                        $rArtist->set_rating($new_rating, $this->user->id);
                    }
                }
            }
        }
        // write to tags
        if ($this->write_tags) {
            global $dic;

            $song          = new Song($rating->id);
            $songTagWriter = $dic->get(SongTagWriterInterface::class);
            $songTagWriter->writeRating($song, $this->user, $rating);
        }
    }

    /**
     * set_flag
     * If you love a song you probably love the artist and the album right?
     * @param Song $song
     * @param boolean $flagged
     */
    public function set_flag($song, $flagged)
    {
        if ($this->match_flags > 0 && $flagged) {
            $album  = new Album($song->album);
            // flag the album
            $fAlbum = new Userflag($song->album, 'album');
            $fAlbum->set_flag($flagged, $this->user->id);
            // and individual disks (if set)
            $fAlbumDisk = new Userflag($song->get_album_disk(), 'album_disk');
            $fAlbumDisk->set_flag($flagged, $this->user->id);
            // rate all the album artists (If there are more than one)
            foreach (Album::get_parent_array($album->id, $album->album_artist) as $artist_id) {
                $fArtist = new Userflag($song->artist, 'artist');
                if (!$fArtist->get_flag($this->user->id, false)) {
                    $fArtist->set_flag($flagged, $this->user->id);
                }
            }
        }
    } // set_flag

    /**
     * save_mediaplay
     * check for extra star rules.
     * @param Song $song
     * @return boolean
     */
    public function save_mediaplay($song)
    {
        // Only support songs
        if (get_class($song) != Song::class) {
            return false;
        }
        // Don't double rate something after it's already been rated before
        $rating = new Rating($song->id, 'song');
        if (($rating->get_user_rating() ?? 0) > 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) AS `counting` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = ? AND `object_id` = ? AND `user` = ?;";

        // get the plays for your user
        $db_results  = Dba::read($sql, array('stream', $song->id, $this->user->id));
        $play_result = Dba::fetch_assoc($db_results);
        $play_count  = (int) $play_result['counting'];

        // get the skips for your user
        $db_results  = Dba::read($sql, array('skip', $song->id, $this->user->id));
        $skip_result = Dba::fetch_assoc($db_results);
        $skip_count  = (int) $skip_result['counting'];

        if ($play_count == 0 && $skip_count == 0) {
            return false;
        }
        if (!empty($this->star1_rule)) {
            if ($this->rule_process($this->star1_rule, $play_count, $skip_count)) {
                $rating->set_rating(1, $this->user->id);
            }
        }
        if (!empty($this->star2_rule)) {
            if ($this->rule_process($this->star2_rule, $play_count, $skip_count)) {
                $rating->set_rating(2, $this->user->id);
            }
        }
        if (!empty($this->star3_rule)) {
            if ($this->rule_process($this->star3_rule, $play_count, $skip_count)) {
                $rating->set_rating(3, $this->user->id);
            }
        }
        if (!empty($this->star4_rule)) {
            if ($this->rule_process($this->star4_rule, $play_count, $skip_count)) {
                $rating->set_rating(4, $this->user->id);
            }
        }
        if (!empty($this->star5_rule)) {
            if ($this->rule_process($this->star5_rule, $play_count, $skip_count)) {
                $rating->set_rating(5, $this->user->id);
            }
        }
        if (!empty($this->flag_rule)) {
            if ($this->rule_process($this->flag_rule, $play_count, $skip_count)) {
                $flag = new Userflag($song->id, 'song');
                if (!$flag->get_flag($this->user->id, false)) {
                    $flag->set_flag(true, $this->user->id);
                }
            }
        }

        return true;
    } // save_mediaplay

    /**
     * rule_process
     * process the rule array and rate/flag depending on the outcome
     * @param array $rule_array
     * @param integer $play_count
     * @param integer $skip_count
     * @return boolean
     */
    public function rule_process($rule_array, $play_count, $skip_count)
    {
        switch (count($rule_array)) {
            case 1:
                $play = (int) $rule_array[0];
                // play count only
                if ($play > 0 && $play_count >= $play) {
                    return true;
                }
                break;
            case 2:
                $play = (int) $rule_array[0];
                $skip = (int) $rule_array[1];
                // play rule and no skip
                if ($play > 0 && $play_count >= $play && $skip == 0) {
                    return true;
                }
                // skip rule and no play
                if ($skip > 0 && $skip_count >= $skip && $play == 0) {
                    return true;
                }
                // check play and skip
                if ($play > 0 && $play_count >= $play && $skip > 0 && $skip_count >= $skip) {
                    return true;
                }
                break;
        }

        return false;
    } // rule_process

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data              = $user->prefs;
        $this->user        = $user;
        $this->min_stars   = (int) $data['ratingmatch_stars'];
        $this->match_flags = (int) $data['ratingmatch_flags'];
        $this->star1_rule  = (isset($data['ratingmatch_star1_rule'])) ? explode(',', (string) $data['ratingmatch_star1_rule']) : array();
        $this->star2_rule  = (isset($data['ratingmatch_star2_rule'])) ? explode(',', (string) $data['ratingmatch_star2_rule']) : array();
        $this->star3_rule  = (isset($data['ratingmatch_star3_rule'])) ? explode(',', (string) $data['ratingmatch_star3_rule']) : array();
        $this->star4_rule  = (isset($data['ratingmatch_star4_rule'])) ? explode(',', (string) $data['ratingmatch_star4_rule']) : array();
        $this->star5_rule  = (isset($data['ratingmatch_star5_rule'])) ? explode(',', (string) $data['ratingmatch_star5_rule']) : array();
        $this->flag_rule   = (isset($data['ratingmatch_flag_rule'])) ? explode(',', (string) $data['ratingmatch_flag_rule']) : array();
        $this->write_tags  = ($data['ratingmatch_write_tags'] == '1');

        return true;
    } // load
}
