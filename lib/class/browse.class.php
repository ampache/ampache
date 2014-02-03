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

/**
 * Browse Class
 *
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 *
 */
class Browse extends Query
{
    public function __construct($id = null, $cached = true)
    {
        parent::__construct($id, $cached);

        if (!$id) {
            $this->set_use_pages(true);
            $this->set_use_alpha(false);
        }
        $this->show_header = true;
    }

    /**
     * set_simple_browse
     * This sets the current browse object to a 'simple' browse method
     * which means use the base query provided and expand from there
     */
    public function set_simple_browse($value)
    {
        $this->set_is_simple($value);

    } // set_simple_browse

    /**
     * add_supplemental_object
     * Legacy function, need to find a better way to do that
     */
    public function add_supplemental_object($class, $uid)
    {
        $_SESSION['browse']['supplemental'][$this->id][$class] = intval($uid);

        return true;

    } // add_supplemental_object

    /**
     * get_supplemental_objects
     * This returns an array of 'class','id' for additional objects that
     * need to be created before we start this whole browsing thing.
     */
    public function get_supplemental_objects()
    {
        $objects = $_SESSION['browse']['supplemental'][$this->id];

        if (!is_array($objects)) { $objects = array(); }

        return $objects;

    } // get_supplemental_objects

    /**
     * show_objects
     * This takes an array of objects
     * and requires the correct template based on the
     * type that we are currently browsing
     */
    public function show_objects($object_ids = null, $argument = null)
    {
        if ($this->is_simple() || !is_array($object_ids)) {
            $object_ids = $this->get_saved();
        } else {
            $this->save_objects($object_ids);
        }

        // Limit is based on the user's preferences if this is not a
        // simple browse because we've got too much here
        if ((count($object_ids) > $this->get_start()) &&
            ! $this->is_simple() &&
            ! $this->is_static_content()) {
            $object_ids = array_slice(
                $object_ids,
                $this->get_start(),
                $this->get_offset(),
                true
            );
        } else if (!count($object_ids)) {
            $this->set_total(0);
        }

        // Load any additional object we need for this
        $extra_objects = $this->get_supplemental_objects();
        $browse = $this;

        foreach ($extra_objects as $class_name => $id) {
            ${$class_name} = new $class_name($id);
        }

        $match = '';
        // Format any matches we have so we can show them to the masses
        if ($filter_value = $this->get_filter('alpha_match')) {
            $match = ' (' . $filter_value . ')';
        } elseif ($filter_value = $this->get_filter('starts_with')) {
            $match = ' (' . $filter_value . ')';
        /*} elseif ($filter_value = $this->get_filter('regex_match')) {
            $match = ' (' . $filter_value . ')';
        } elseif ($filter_value = $this->get_filter('regex_not_match')) {
            $match = ' (' . $filter_value . ')';*/
        } elseif ($filter_value = $this->get_filter('catalog')) {
            // Get the catalog title
            $catalog = Catalog::create_from_id($filter_value);
            $match = ' (' . $catalog->name . ')';
        }

        $type = $this->get_type();

        // Set the correct classes based on type
        $class = "box browse_" . $type;

        // Switch on the type of browsing we're doing
        switch ($type) {
            case 'song':
                $box_title = T_('Songs') . $match;
                Song::build_cache($object_ids);
                $box_req = AmpConfig::get('prefix') . '/templates/show_songs.inc.php';
            break;
            case 'album':
                $box_title = T_('Albums') . $match;
                Album::build_cache($object_ids,'extra');
                $box_req = AmpConfig::get('prefix') . '/templates/show_albums.inc.php';
            break;
            case 'user':
                $box_title = T_('Manage Users') . $match;
                $box_req = AmpConfig::get('prefix') . '/templates/show_users.inc.php';
            break;
            case 'artist':
                $box_title = T_('Artists') . $match;
                Artist::build_cache($object_ids,'extra');
                $box_req = AmpConfig::get('prefix') . '/templates/show_artists.inc.php';
            break;
            case 'live_stream':
                require_once AmpConfig::get('prefix') . '/templates/show_live_stream.inc.php';
                $box_title = T_('Radio Stations') . $match;
                $box_req = AmpConfig::get('prefix') . '/templates/show_live_streams.inc.php';
            break;
            case 'playlist':
                Playlist::build_cache($object_ids);
                $box_title = T_('Playlists') . $match;
                $box_req = AmpConfig::get('prefix') . '/templates/show_playlists.inc.php';
            break;
            case 'playlist_song':
                $box_title = T_('Playlist Songs') . $match;
                $box_req = AmpConfig::get('prefix') . '/templates/show_playlist_songs.inc.php';
            break;
            case 'playlist_localplay':
                $box_title = T_('Current Playlist');
                $box_req = AmpConfig::get('prefix') . '/templates/show_localplay_playlist.inc.php';
                UI::show_box_bottom();
            break;
            case 'smartplaylist':
                $box_title = T_('Smart Playlists') . $match;
                $box_req = AmpConfig::get('prefix') . '/templates/show_smartplaylists.inc.php';
            break;
            case 'catalog':
                $box_title = T_('Catalogs');
                $box_req = AmpConfig::get('prefix') . '/templates/show_catalogs.inc.php';
            break;
            case 'shoutbox':
                $box_title = T_('Shoutbox Records');
                $box_req = AmpConfig::get('prefix') . '/templates/show_manage_shoutbox.inc.php';
            break;
            case 'tag':
                Tag::build_cache($tags);
                $box_title = T_('Tag Cloud');
                $box_req = AmpConfig::get('prefix') . '/templates/show_tagcloud.inc.php';
            break;
            case 'video':
                Video::build_cache($object_ids);
                $box_title = T_('Videos');
                $box_req = AmpConfig::get('prefix') . '/templates/show_videos.inc.php';
            break;
            case 'democratic':
                $box_title = T_('Democratic Playlist');
                $box_req = AmpConfig::get('prefix') . '/templates/show_democratic_playlist.inc.php';
            break;
            case 'wanted':
                $box_title = T_('Wanted Albums');
                $box_req = AmpConfig::get('prefix') . '/templates/show_wanted_albums.inc.php';
            break;
            case 'share':
                $box_title = T_('Shared Objects');
                $box_req = AmpConfig::get('prefix') . '/templates/show_shared_objects.inc.php';
            break;
            case 'song_preview':
                $box_title = T_('Songs');
                $box_req = AmpConfig::get('prefix') . '/templates/show_song_previews.inc.php';
            break;
            case 'channel':
                $box_title = T_('Channels');
                $box_req = AmpConfig::get('prefix') . '/templates/show_channels.inc.php';
            break;
            default:
                // Rien a faire
            break;
        } // end switch on type

        Ajax::start_container('browse_content_' . $type, 'browse_content');
        if ($this->get_show_header()) {
            if ($box_req) {
                UI::show_box_top($box_title, $class);
            }
        }

        if ($box_req) {
            require_once $box_req;
        }

        if ($this->get_show_header()) {
            if ($box_req) {
                UI::show_box_bottom();
            }
            echo '<script type="text/javascript">';
            echo Ajax::action('?page=browse&action=get_filters&browse_id=' . $this->id, '');
            echo ';</script>';
        } else {
            if (!$this->get_use_pages()) {
                $this->show_next_link();
            }
        }
        Ajax::end_container();

    } // show_object

    public function show_next_link()
    {
        $limit    = $this->get_offset();
        $start    = $this->get_start();
        $total    = $this->get_total();
        $next_offset = $start + $limit;
        if ($next_offset <= $total) {
            echo '<a class="jscroll-next" href="' . AmpConfig::get('ajax_url') . '?page=browse&action=page&browse_id=' . $this->id . '&start=' . $next_offset . '&xoutput=raw&xoutputnode=browse_content_' . $this->get_type() . '&show_header=false">' . T_('More') . '</a>';
        }
    }

    /**
      * set_filter_from_request
     * //FIXME
     */
    public function set_filter_from_request($request)
    {
        foreach ($request as $key => $value) {
            //reinterpret v as a list of int
            $list = explode(',', $value);
            $ok = true;
            foreach ($list as $item) {
                if (!is_numeric($item)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                if (sizeof($list) == 1) {
                    $this->set_filter($key, $list[0]);
                }
            } else {
                $this->set_filter($key, $list);
            }
        }
    } // set_filter_from_request

    public function set_use_pages($use_pages)
    {
        $this->_state['use_pages'] = $use_pages;
    }

    public function get_use_pages()
    {
        return $this->_state['use_pages'];
    }

    public function set_use_alpha($use_alpha)
    {
        $this->_state['use_alpha'] = $use_alpha;
    }

    public function get_use_alpha()
    {
        return $this->_state['use_alpha'];
    }

    public function set_show_header($show_header)
    {
        $this->show_header = $show_header;
    }

    public function get_show_header()
    {
        return $this->show_header;
    }

} // browse
