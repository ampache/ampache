<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

use MusicBrainz\MusicBrainz;
use MusicBrainz\Clients\RequestsMbClient;

class Wanted extends database_object
{
    /**
     * Constructor
     */
    public function __construct($id=0)
    {
        if (!$id) { return true; }

        /* Get the information from the db */
        $info = $this->get_info($id);

        // Foreach what we've got
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        return true;
    } //constructor

    /**
     * get_missing_albums
     * Get list of library's missing albums from MusicBrainz
     */
    public static function get_missing_albums($artist)
    {
        $mb = new MusicBrainz(new RequestsMbClient());
        $includes = array(
            'release-groups'
        );
        $types = explode(',', AmpConfig::get('wanted_types'));

        try {
            $martist = $mb->lookup('artist', $artist->mbid, $includes);
        } catch (Exception $e) {
            return null;
        }

        $owngroups = array();
        $albums = $artist->get_albums();
        foreach ($albums as $id) {
            $album = new Album($id);
            if ($album->mbid) {
                $malbum = $mb->lookup('release', $album->mbid, array('release-groups'));
                if ($malbum->{'release-group'}) {
                    if (!in_array($malbum->{'release-group'}->id, $owngroups)) {
                        $owngroups[] = $malbum->{'release-group'}->id;
                    }
                }
            }
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
                        $wanted = new Wanted($wantedid);
                        if ($wanted->id) {
                            $wanted->format();
                        } else {
                            $wanted->mbid = $group->id;
                            $wanted->artist = $artist->id;
                            $wanted->name = $group->title;
                            $wanted->year = date("Y", strtotime($group->{'first-release-date'}));
                            $wanted->accepted = false;
                            $wanted->f_artist_link = $artist->f_name_link;
                            $wanted->f_user = $GLOBALS['user']->fullname;
                        }
                        $results[] = $wanted;
                    }
                }
            }
        }

        return $results;
    } // get_missing_albums

    public static function get_wanted($mbid)
    {
        $sql = "SELECT `id` FROM `wanted` WHERE `mbid` = ?";
        $db_results = Dba::read($sql, array($mbid));
        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }

        return false;
    }

    public static function delete_wanted($mbid)
    {
        $sql = "DELETE FROM `wanted` WHERE `mbid` = ?";
        $params = array( $mbid );
        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= " AND `user` = ?";
            $params[] = $GLOBALS['user']->id;
        }

        Dba::write($sql, $params);
    }

    public function accept()
    {
        if ($GLOBALS['user']->has_access('75')) {
            $sql = "UPDATE `wanted` SET `accepted` = '1' WHERE `mbid` = ?";
            Dba::write($sql, array( $this->mbid ));
            $this->accepted = 1;
            
            foreach (Plugin::get_plugins('process_wanted') as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load()) {
                    $plugin->_plugin->process_wanted($this);
                }
            }
        }
    }

    public static function has_wanted($mbid, $userid = 0)
    {
        if ($userid == 0) {
            $userid = $GLOBALS['user']->id;
        }

        $sql = "SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?";
        $db_results = Dba::read($sql, array($mbid, $userid));

        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['id'];
        }

        return false;

    }

    public static function add_wanted($mbid, $artist, $name, $year)
    {
        $sql = "INSERT INTO `wanted` (`user`, `artist`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $accept = $GLOBALS['user']->has_access('75') ? true : AmpConfig::get('wanted_auto_accept');
        $params = array($GLOBALS['user']->id, $artist, $mbid, $name, $year, time(), ($accept ? '1' : '0'));
        Dba::write($sql, $params);
    }

    public function show_action_buttons()
    {
        if ($this->id) {
            if (!$this->accepted) {
                if ($GLOBALS['user']->has_access('75')) {
                    echo Ajax::button('?page=index&action=accept_wanted&mbid=' . $this->mbid,'enable', T_('Accept'),'wanted_accept_' . $this->mbid);
                }
            }
            if ($GLOBALS['user']->has_access('75') || (Wanted::has_wanted($this->mbid) && $this->accepted != '1')) {
                echo " " . Ajax::button('?page=index&action=remove_wanted&mbid=' . $this->mbid,'disable', T_('Remove'),'wanted_remove_' . $this->mbid);
            }
        } else {
            echo Ajax::button('?page=index&action=add_wanted&mbid=' . $this->mbid . '&artist=' . $this->artist . '&name=' . urlencode($this->name) . '&year=' . $this->year,'add_wanted', T_('Add to wanted list'),'wanted_add_' . $this->mbid);
        }
    }

    public function format()
    {
        if ($this->id) {
            $artist = new Artist($this->artist);
            $artist->format();
            $this->f_artist_link = $artist->f_name_link;
            $user = new User($this->user);
            $this->f_user = $user->fullname;
        }

    }

    public static function get_wanted_list_sql()
    {
        $sql = "SELECT `id` FROM `wanted` ";

        if (!$GLOBALS['user']->has_access('75')) {
            $sql .= "WHERE `user` = '" . scrub_in($GLOBALS['user']->id) . "'";
        }

        return $sql;
    }

    public static function get_wanted_list()
    {
        $sql = self::get_wanted_list_sql();
        $db_results = Dba::read($sql);
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

} // end of recommendation class
