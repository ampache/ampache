<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use MusicBrainz\Filters\ArtistFilter;

class Wanted extends database_object
{
    /* Variables from DB */

    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $mbid
     */
    public $mbid;
    /**
     * @var int $artist
     */
    public $artist;
    /**
     * @var string $artist_mbid
     */
    public $artist_mbid;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var string $year
     */
    public $year;
    /**
     * @var boolean $accepted
     */
    public $accepted;
    /**
     * @var string $release_mbid
     */
    public $release_mbid;
    /**
     * @var int $user
     */
    public $user;

    /**
     * @var string $link
     */
    public $link;

    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_artist_link
     */
    public $f_artist_link;
    /**
     * @var string $f_user
     */
    public $f_user;
    /**
     * @var array $songs
     */
    public $songs;

    /**
     * Constructor
     * @param int $id
     */
    public function __construct($id=0)
    {
        if (!$id) {
            return true;
        }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    /**
     * get_missing_albums
     * Get list of library's missing albums from MusicBrainz
     * @param Artist|null $artist
     * @param string $mbid
     * @return array
     */
    public static function get_missing_albums($artist, $mbid='')
    {
        $mb       = new MusicBrainz(new RequestsHttpAdapter());
        $includes = array(
            'release-groups'
        );
        $types = explode(',', AmpConfig::get('wanted_types'));

        try {
            $martist = $mb->lookup('artist', $artist ? $artist->mbid : $mbid, $includes);
        } catch (Exception $e) {
            return null;
        }

        $owngroups = array();
        $wartist   = array();
        if ($artist) {
            $albums = $artist->get_albums();
            foreach ($albums as $id) {
                $album = new Album($id);
                if (trim($album->mbid_group)) {
                    $owngroups[] = $album->mbid_group;
                } else {
                    if (trim($album->mbid)) {
                        $malbum = $mb->lookup('release', $album->mbid, array('release-groups'));
                        if ($malbum->{'release-group'}) {
                            if (!in_array($malbum->{'release-group'}->id, $owngroups)) {
                                $owngroups[] = $malbum->{'release-group'}->id;
                            }
                        }
                    }
                }
            }
        } else {
            $wartist['mbid'] = $mbid;
            $wartist['name'] = $martist->name;
            parent::add_to_cache('missing_artist', $mbid, $wartist);
            $wartist = self::get_missing_artist($mbid);
        }

        $results = array();
        foreach ($martist->{'release-groups'} as $group) {
            if (in_array(strtolower($group->{'primary-type'}), $types)) {
                $add = true;

                for ($i = 0; $i < count($group->{'secondary-types'}) && $add; ++$i) {
                    $add = in_array(strtolower($group->{'secondary-types'}[$i]), $types);
                }

                if ($add) {
                    if (!in_array($group->id, $owngroups)) {
                        $wantedid = self::get_wanted($group->id);
                        $wanted   = new Wanted($wantedid);
                        if ($wanted->id) {
                            $wanted->format();
                        } else {
                            $wanted->mbid = $group->id;
                            if ($artist) {
                                $wanted->artist = $artist->id;
                            } else {
                                $wanted->artist_mbid = $mbid;
                            }
                            $wanted->name = $group->title;
                            if (!empty($group->{'first-release-date'})) {
                                if (strlen($group->{'first-release-date'}) == 4) {
                                    $wanted->year = $group->{'first-release-date'};
                                } else {
                                    $wanted->year = date("Y", strtotime($group->{'first-release-date'}));
                                }
                            }
                            $wanted->accepted = false;
                            $wanted->link     = AmpConfig::get('web_path') . "/albums.php?action=show_missing&mbid=" . $group->id;
                            if ($artist) {
                                $wanted->link .= "&artist=" . $wanted->artist;
                            } else {
                                $wanted->link .= "&artist_mbid=" . $mbid;
                            }
                            $wanted->f_link        = "<a href=\"" . $wanted->link . "\" title=\"" . $wanted->name . "\">" . $wanted->name . "</a>";
                            $wanted->f_artist_link = $artist ? $artist->f_link : $wartist['link'];
                            $wanted->f_user        = $GLOBALS['user']->f_name;
                        }
                        $results[] = $wanted;
                    }
                }
            }
        }

        return $results;
    } // get_missing_albums

    /**
     * Get missing artist data.
     * @param string $mbid
     * @return array
     */
    public static function get_missing_artist($mbid)
    {
        $wartist = array();

        if (parent::is_cached('missing_artist', $mbid)) {
            $wartist = parent::get_from_cache('missing_artist', $mbid);
        } else {
            $mb              = new MusicBrainz(new RequestsHttpAdapter());
            $wartist['mbid'] = $mbid;
            $wartist['name'] = T_('Unknown Artist');

            try {
                $martist = $mb->lookup('artist', $mbid);
            } catch (Exception $e) {
                return $wartist;
            }

            $wartist['name'] = $martist->name;
            parent::add_to_cache('missing_artist', $mbid, $wartist);
        }

        $wartist['link'] = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show_missing&mbid=" . $wartist['mbid'] . "\" title=\"" . $wartist['name'] . "\">" . $wartist['name'] . "</a>";

        return $wartist;
    }

    public static function search_missing_artists($name)
    {
        $args = array(
            'artist' => $name
        );
        $filter   = new ArtistFilter($args);
        $mb       = new MusicBrainz(new RequestsHttpAdapter());
        $res      = $mb->search($filter);
        $wartists = array();
        foreach ($res as $r) {
            $wartists[] = array(
                'mbid' => $r->id,
                'name' => $r->name,
            );
        }

        return $wartists;
    }

    /**
     * Get accepted wanted release count.
     * @return int
     */
    public static function get_accepted_wanted_count()
    {
        $sql        = "SELECT COUNT(`id`) AS `wanted_cnt` FROM `wanted` WHERE `accepted` = 1";
        $db_results = Dba::read($sql);
        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['wanted_cnt'];
        }

        return 0;
    }

    /**
     * Get wanted release by mbid.
     * @param string $mbid
     * @return int
     */
    public static function get_wanted($mbid)
    {
        $sql        = "SELECT `id` FROM `wanted` WHERE `mbid` = ?";
        $db_results = Dba::read($sql, array($mbid));
        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }

        return 0;
    }

    /**
     * Delete wanted release.
     * @param string $mbid
     */
    public static function delete_wanted($mbid)
    {
        $sql    = "DELETE FROM `wanted` WHERE `mbid` = ?";
        $params = array( $mbid );
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        Dba::write($sql, $params);
    }

    /**
     * Delete a wanted release by mbid.
     * @param string $mbid
     */
    public static function delete_wanted_release($mbid)
    {
        if (self::get_accepted_wanted_count() > 0) {
            $mb     = new MusicBrainz(new RequestsHttpAdapter());
            $malbum = $mb->lookup('release', $mbid, array('release-groups'));
            if ($malbum->{'release-group'}) {
                self::delete_wanted($malbum->{'release-group'});
            }
        }
    }

    /**
     * Delete a wanted release by name.
     * @param int $artist
     * @param string $album_name
     * @param int $year
     */
    public static function delete_wanted_by_name($artist, $album_name, $year)
    {
        $sql    = "DELETE FROM `wanted` WHERE `artist` = ? AND `name` = ? AND `year` = ?";
        $params = array( $artist, $album_name, $year );
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        Dba::write($sql, $params);
    }

    /**
     * Accept a wanted request.
     */
    public function accept()
    {
        if ($GLOBALS['user']->has_access('75')) {
            $sql = "UPDATE `wanted` SET `accepted` = '1' WHERE `mbid` = ?";
            Dba::write($sql, array( $this->mbid ));
            $this->accepted = true;

            foreach (Plugin::get_plugins('process_wanted') as $plugin_name) {
                debug_event('wanted', 'Using Wanted Process plugin: ' . $plugin_name, '5');
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($GLOBALS['user'])) {
                    $plugin->_plugin->process_wanted($this);
                }
            }
        }
    }

    /**
     * Check if a release mbid is already marked as wanted
     * @param string $mbid
     * @param int $userid
     * @return boolean
     */
    public static function has_wanted($mbid, $userid = 0)
    {
        if ($userid == 0) {
            $userid = $GLOBALS['user']->id;
        }

        $sql        = "SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?";
        $db_results = Dba::read($sql, array($mbid, $userid));

        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }

        return false;
    }

    /**
     * Add a new wanted release.
     * @param string $mbid
     * @param int $artist
     * @param string $artist_mbid
     * @param string $name
     * @param int $year
     */
    public static function add_wanted($mbid, $artist, $artist_mbid, $name, $year)
    {
        $sql    = "INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $accept = $GLOBALS['user']->has_access('75') ? true : AmpConfig::get('wanted_auto_accept');
        $params = array($GLOBALS['user']->id, $artist, $artist_mbid, $mbid, $name, $year, time(), '0');
        Dba::write($sql, $params);

        if ($accept) {
            $wantedid = Dba::insert_id();
            $wanted   = new Wanted($wantedid);
            $wanted->accept();

            database_object::remove_from_cache('wanted', $wantedid);
        }
    }

    /**
     * Show action buttons.
     */
    public function show_action_buttons()
    {
        if ($this->id) {
            if (!$this->accepted) {
                if ($GLOBALS['user']->has_access('75')) {
                    echo Ajax::button('?page=index&action=accept_wanted&mbid=' . $this->mbid, 'enable', T_('Accept'), 'wanted_accept_' . $this->mbid);
                }
            }
            if ($GLOBALS['user']->has_access('75') || (Wanted::has_wanted($this->mbid) && $this->accepted != '1')) {
                echo " " . Ajax::button('?page=index&action=remove_wanted&mbid=' . $this->mbid, 'disable', T_('Remove'), 'wanted_remove_' . $this->mbid);
            }
        } else {
            echo Ajax::button('?page=index&action=add_wanted&mbid=' . $this->mbid . ($this->artist ? '&artist=' . $this->artist : '&artist_mbid=' . $this->artist_mbid) . '&name=' . urlencode($this->name) . '&year=' . $this->year, 'add_wanted', T_('Add to wanted list'), 'wanted_add_' . $this->mbid);
        }
    }

    /**
     * Load wanted release data.
     * @param boolean $track_details
     */
    public function load_all($track_details = true)
    {
        $mb          = new MusicBrainz(new RequestsHttpAdapter());
        $this->songs = array();

        try {
            $group = $mb->lookup('release-group', $this->mbid, array( 'releases' ));
            // Set fresh data
            $this->name = $group->title;
            $this->year = date("Y", strtotime($group->{'first-release-date'}));

            // Load from database if already cached
            $this->songs = Song_preview::get_song_previews($this->mbid);
            if (count($group->releases) > 0) {
                $this->release_mbid = $group->releases[0]->id;
                if ($track_details && count($this->songs) == 0) {
                    // Use the first release as reference for track content
                    $release = $mb->lookup('release', $this->release_mbid, array( 'recordings' ));
                    foreach ($release->media as $media) {
                        foreach ($media->tracks as $track) {
                            $song          = array();
                            $song['disk']  = $media->position;
                            $song['track'] = $track->number;
                            $song['title'] = $track->title;
                            $song['mbid']  = $track->id;
                            if ($this->artist) {
                                $song['artist'] = $this->artist;
                            }
                            $song['artist_mbid'] = $this->artist_mbid;
                            $song['session']     = session_id();
                            $song['album_mbid']  = $this->mbid;

                            if ($this->artist) {
                                $artist      = new Artist($this->artist);
                                $artist_name = $artist->name;
                            } else {
                                $wartist     = Wanted::get_missing_artist($this->artist_mbid);
                                $artist_name = $wartist['name'];
                            }

                            $song['file'] = null;
                            foreach (Plugin::get_plugins('get_song_preview') as $plugin_name) {
                                $plugin = new Plugin($plugin_name);
                                if ($plugin->load($GLOBALS['user'])) {
                                    $song['file'] = $plugin->_plugin->get_song_preview($track->id, $artist_name, $track->title);
                                    if ($song['file'] != null) {
                                        break;
                                    }
                                }
                            }

                            if ($song != null) {
                                $this->songs[] = new Song_Preview(Song_preview::insert($song));
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->songs = array();
        }

        foreach ($this->songs as $song) {
            $song->f_album = $this->name;
            $song->format();
        }
    }

    /**
     * Format data.
     */
    public function format()
    {
        if ($this->artist) {
            $artist = new Artist($this->artist);
            $artist->format();
            $this->f_artist_link = $artist->f_link;
        } else {
            $wartist             = Wanted::get_missing_artist($this->artist_mbid);
            $this->f_artist_link = $wartist['link'];
        }
        $this->link   = AmpConfig::get('web_path') . "/albums.php?action=show_missing&mbid=" . $this->mbid . "&artist=" . $this->artist . "&artist_mbid=" . $this->artist_mbid . "\" title=\"" . $this->name;
        $this->f_link = "<a href=\"" . $this->link . "\">" . $this->name . "</a>";
        $user         = new User($this->user);
        $user->format();
        $this->f_user = $user->f_name;
    }

    /**
     * Get wanted list sql.
     * @return string
     */
    public static function get_wanted_list_sql()
    {
        $sql = "SELECT `id` FROM `wanted` ";

        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= "WHERE `user` = '" . scrub_in($GLOBALS['user']->id) . "'";
        }

        return $sql;
    }

    /**
     * Get wanted list.
     * @return int[]
     */
    public static function get_wanted_list()
    {
        $sql        = self::get_wanted_list_sql();
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }
} // end of recommendation class
