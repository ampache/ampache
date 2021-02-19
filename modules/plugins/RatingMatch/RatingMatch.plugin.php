<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

class AmpacheRatingMatch
{
    public $name        = 'RatingMatch';
    public $categories  = 'scrobbling';
    public $description = 'Raise the album and artist rating to match the highest song rating';
    public $version     = '000003';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $min_stars;
    private $match_flags;
    private $user_id;
    private $star1_rule;
    private $star2_rule;
    private $star3_rule;
    private $star4_rule;
    private $star5_rule;
    private $flag_rule;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Raise the album and artist rating to match the highest song rating');

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
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        $from_version = Plugin::get_plugin_version($this->name);
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
        if ($rating->type == 'song' && $new_rating >= $this->min_stars && $this->min_stars > 0) {
            $song   = new Song($rating->id);
            $artist = new Rating($song->artist, 'artist');
            $album  = new Rating($song->album, 'album');

            $rating_artist = (int) $artist->get_user_rating($this->user_id);
            $rating_album  = (int) $album->get_user_rating($this->user_id);
            if ($rating_artist < $new_rating) {
                $artist->set_rating($new_rating, $this->user_id);
            }
            if ($rating_album < $new_rating) {
                $album->set_rating($new_rating, $this->user_id);
            }
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
            $album  = new Userflag($song->album, 'album');
            $artist = new Userflag($song->artist, 'artist');
            if (!$album->get_flag($this->user_id, false)) {
                $album->set_flag($flagged, $this->user_id);
            }
            if (!$artist->get_flag($this->user_id, false)) {
                $artist->set_flag($flagged, $this->user_id);
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
        if (get_class($song) != 'Song') {
            return false;
        }
        // Don't double rate something after it's already been rated before
        $rating = new Rating($song->id, 'song');
        if (($rating->get_user_rating() ?: 0) > 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) AS `counting` " .
            "FROM object_count WHERE object_type = 'song' AND " .
            "`count_type` = ? AND object_id = ? AND user = ?;";

        // get the plays for your user
        $db_results  = Dba::read($sql, array('stream', $song->id, $this->user_id));
        $play_result = Dba::fetch_assoc($db_results);
        $play_count  = (int) $play_result['counting'];

        // get the skips for your user
        $db_results  = Dba::read($sql, array('skip', $song->id, $this->user_id));
        $skip_result = Dba::fetch_assoc($db_results);
        $skip_count  = (int) $skip_result['counting'];

        if ($play_count == 0 && $skip_count == 0) {
            return false;
        }
        if (!empty($this->star1_rule)) {
            if ($this->rule_process($this->star1_rule, $play_count, $skip_count)) {
                $rating->set_rating(1, $this->user_id);
            }
        }
        if (!empty($this->star2_rule)) {
            if ($this->rule_process($this->star2_rule, $play_count, $skip_count)) {
                $rating->set_rating(2, $this->user_id);
            }
        }
        if (!empty($this->star3_rule)) {
            if ($this->rule_process($this->star3_rule, $play_count, $skip_count)) {
                $rating->set_rating(3, $this->user_id);
            }
        }
        if (!empty($this->star4_rule)) {
            if ($this->rule_process($this->star4_rule, $play_count, $skip_count)) {
                $rating->set_rating(4, $this->user_id);
            }
        }
        if (!empty($this->star5_rule)) {
            if ($this->rule_process($this->star5_rule, $play_count, $skip_count)) {
                $rating->set_rating(5, $this->user_id);
            }
        }
        if (!empty($this->flag_rule)) {
            if ($this->rule_process($this->flag_rule, $play_count, $skip_count)) {
                $flag = new Userflag($song->id, 'song');
                if (!$flag->get_flag($this->user_id, false)) {
                    $flag->set_flag(true, $this->user_id);
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
        $this->user_id     = $user->id;
        $this->min_stars   = (int) $data['ratingmatch_stars'];
        $this->match_flags = (int) $data['ratingmatch_flags'];
        $this->star1_rule  = (isset($data['ratingmatch_star1_rule'])) ? explode(',', (string) $data['ratingmatch_star1_rule']) : array();
        $this->star2_rule  = (isset($data['ratingmatch_star2_rule'])) ? explode(',', (string) $data['ratingmatch_star2_rule']) : array();
        $this->star3_rule  = (isset($data['ratingmatch_star3_rule'])) ? explode(',', (string) $data['ratingmatch_star3_rule']) : array();
        $this->star4_rule  = (isset($data['ratingmatch_star4_rule'])) ? explode(',', (string) $data['ratingmatch_star4_rule']) : array();
        $this->star5_rule  = (isset($data['ratingmatch_star5_rule'])) ? explode(',', (string) $data['ratingmatch_star5_rule']) : array();
        $this->flag_rule   = (isset($data['ratingmatch_flag_rule'])) ? explode(',', (string) $data['ratingmatch_flag_rule']) : array();

        return true;
    } // load
} // end AmpacheRatingMatch
