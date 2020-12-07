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
    public $categories  = 'metadata';
    public $description = 'Raise the album and artist rating to match the highest song rating';
    public $url;
    public $version     = '000002';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $min_stars;
    private $match_flags;

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

        Preference::insert('ratingmatch_stars', T_('Minimum star rating to match'), 0, 25, 'integer', 'plugins', $this->name);
        Preference::insert('ratingmatch_flags', T_('When you love a track, flag the album and artist'), 0, 25, 'boolean', 'plugins', $this->name);

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

        return true;
    } // load
} // end AmpacheRatingMatch
