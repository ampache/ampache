<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * This contains functions that are generic, and display information
 * things like a confirmation box, etc and so forth
 *
 */

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

/**
 * show_confirmation
 *
 * shows a confirmation of an action
 *
 * @param string $title The Title of the message
 * @param string $text The details of the message
 * @param string $next_url Where to go next
 * @param integer $cancel T/F show a cancel button that uses return_referer()
 * @param string $form_name
 * @param boolean $visible
 */
function show_confirmation($title, $text, $next_url, $cancel = 0, $form_name = 'confirmation', $visible = true)
{
    if (substr_count($next_url, AmpConfig::get('web_path'))) {
        $path = $next_url;
    } else {
        $path = AmpConfig::get('web_path') . "/$next_url";
    }

    require AmpConfig::get('prefix') . UI::find_template('show_confirmation.inc.php');
} // show_confirmation

/**
 * @param $action
 * @param $catalogs
 * @param array $options
 */
function catalog_worker($action, $catalogs = null, $options = null)
{
    if (AmpConfig::get('ajax_load')) {
        $sse_url = AmpConfig::get('web_path') . "/server/sse.server.php?worker=catalog&action=" . $action . "&catalogs=" . urlencode(json_encode($catalogs));
        if ($options) {
            $sse_url .= "&options=" . urlencode(json_encode($_POST));
        }
        sse_worker($sse_url);
    } else {
        Catalog::process_action($action, $catalogs, $options);
    }
}

/**
 * @param string $url
 */
function sse_worker($url)
{
    echo '<script>';
    echo "sse_worker('$url');";
    echo "</script>\n";
}

/**
 * return_referer
 * returns the script part of the referer address passed by the web browser
 * this is not %100 accurate. Also because this is not passed by us we need
 * to clean it up, take the filename then check for a /admin/ and dump the rest
 * @return string
 */
function return_referer()
{
    $referer = $_SERVER['HTTP_REFERER'];
    if (substr($referer, -1) == '/') {
        $file = 'index.php';
    } else {
        $file = basename($referer);
        /* Strip off the filename */
        $referer = substr($referer, 0, strlen((string) $referer) - strlen((string) $file));
    }

    if (substr($referer, strlen((string) $referer) - 6, 6) == 'admin/') {
        $file = 'admin/' . $file;
    }

    return $file;
} // return_referer

/**
 * get_location
 * This function gets the information about a person's current location.
 * This is used for A) sidebar highlighting & submenu showing and B) titlebar
 * information. It returns an array of information about what they are currently
 * doing.
 * Possible array elements
 * ['title']    Text name for the page
 * ['page']    actual page name
 * ['section']    name of the section we are in, admin, browse etc (submenu)
 */
function get_location()
{
    $location = array();

    if (strlen((string) $_SERVER['PHP_SELF'])) {
        $source = $_SERVER['PHP_SELF'];
    } else {
        $source = $_SERVER['REQUEST_URI'];
    }

    /* Sanatize the $_SERVER['PHP_SELF'] variable */
    $source           = str_replace(AmpConfig::get('raw_web_path'), "", $source);
    $location['page'] = preg_replace("/^\/(.+\.php)\/?.*/", "$1", $source);

    switch ($location['page']) {
        case 'index.php':
            $location['title'] = T_('Home');
            break;
        case 'upload.php':
            $location['title'] = T_('Upload');
            break;
        case 'localplay.php':
            $location['title'] = T_('Localplay');
            break;
        case 'randomplay.php':
            $location['title'] = T_('Random Play');
            break;
        case 'playlist.php':
            $location['title'] = T_('Playlist');
            break;
        case 'search.php':
            $location['title'] = T_('Search');
            break;
        case 'preferences.php':
            $location['title'] = T_('Preferences');
            break;
        case 'admin/catalog.php':
        case 'admin/index.php':
            $location['title']   = T_('Admin-Catalog');
            $location['section'] = 'admin';
            break;
        case 'admin/users.php':
            $location['title']   = T_('Admin-User Management');
            $location['section'] = 'admin';
            break;
        case 'admin/mail.php':
            $location['title']   = T_('Admin-Mail Users');
            $location['section'] = 'admin';
            break;
        case 'admin/access.php':
            $location['title']   = T_('Admin-Manage Access Lists');
            $location['section'] = 'admin';
            break;
        case 'admin/preferences.php':
            $location['title']   = T_('Admin-Site Preferences');
            $location['section'] = 'admin';
            break;
        case 'admin/modules.php':
            $location['title']   = T_('Admin-Manage Modules');
            $location['section'] = 'admin';
            break;
        case 'browse.php':
            $location['title']   = T_('Browse Music');
            $location['section'] = 'browse';
            break;
        case 'albums.php':
            $location['title']   = T_('Albums');
            $location['section'] = 'browse';
            break;
        case 'artists.php':
            $location['title']   = T_('Artists');
            $location['section'] = 'browse';
            break;
        case 'stats.php':
            $location['title'] = T_('Statistics');
            break;
        default:
            $location['title'] = '';
            break;
    } // switch on raw page location

    return $location;
} // get_location

/**
 * show_preference_box
 * This shows the preference box for the preferences pages.
 * @param $preferences
 */
function show_preference_box($preferences)
{
    require AmpConfig::get('prefix') . UI::find_template('show_preference_box.inc.php');
} // show_preference_box

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be
 * hella long). It's used by the Edit page and takes a $name and a $album_id
 * @param string $name
 * @param integer $album_id
 * @param boolean $allow_add
 * @param integer $song_id
 * @param boolean $allow_none
 * @param string $user
 */
function show_album_select($name, $album_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user = null)
{
    static $album_id_cnt = 0;

    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = "album_select_" . $song_id;
    } else {
        $key = "album_select_c" . ++$album_id_cnt;
    }

    $sql    = "SELECT `album`.`id`, `album`.`name`, `album`.`prefix`, `disk` FROM `album`";
    $params = array();
    if ($user !== null) {
        $sql .= "INNER JOIN `artist` ON `artist`.`id` = `album`.`album_artist` WHERE `album`.`album_artist` IS NOT NULL AND `artist`.`user` = ? ";
        $params[] = $user;
    }
    $sql .= "ORDER BY `album`.`name`";
    $db_results = Dba::read($sql, $params);
    $count      = Dba::num_rows($db_results);

    // Added ID field so we can easily observe this element
    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected   = '';
        $album_name = trim((string) $row['prefix'] . " " . $row['name']);
        if (!AmpConfig::get('album_group') && (int) $count > 1) {
            $album_name .= " [" . T_('Disk') . " " . $row['disk'] . "]";
        }
        if ($row['id'] == $album_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($album_name) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    if ($count === 0) {
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ");</script>\n";
    }
} // show_album_select

/**
 * show_artist_select
 * This is the same as show_album_select except it's *gasp* for artists! How
 * inventive!
 * @param string $name
 * @param integer $artist_id
 * @param boolean $allow_add
 * @param integer $song_id
 * @param boolean $allow_none
 * @param integer $user_id
 */
function show_artist_select($name, $artist_id = 0, $allow_add = false, $song_id = 0, $allow_none = false, $user_id = null)
{
    static $artist_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($song_id) {
        $key = $name . "_select_" . $song_id;
    } else {
        $key = $name . "_select_c" . ++$artist_id_cnt;
    }

    $sql    = "SELECT `id`, `name`, `prefix` FROM `artist` ";
    $params = array();
    if ($user_id !== null) {
        $sql .= "WHERE `user` = ? ";
        $params[] = $user_id;
    }
    $sql .= "ORDER BY `name`";
    $db_results = Dba::read($sql, $params);
    $count      = Dba::num_rows($db_results);

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected    = '';
        $artist_name = trim((string) $row['prefix'] . " " . $row['name']);
        if ($row['id'] == $artist_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($artist_name) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_('Add New') . "...</option>\n";
    }

    echo "</select>\n";

    if ($count === 0) {
        echo "<script>check_inline_song_edit('" . $name . "', " . $song_id . ");</script>\n";
    }
} // show_artist_select

/**
 * show_tvshow_select
 * This is the same as show_album_select except it's *gasp* for tvshows! How
 * inventive!
 * @param string $name
 * @param integer $tvshow_id
 * @param boolean $allow_add
 * @param integer $season_id
 * @param boolean $allow_none
 */
function show_tvshow_select($name, $tvshow_id = 0, $allow_add = false, $season_id = 0, $allow_none = false)
{
    static $tvshow_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($season_id) {
        $key = $name . "_select_" . $season_id;
    } else {
        $key = $name . "_select_c" . ++$tvshow_id_cnt;
    }

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    $sql        = "SELECT `id`, `name` FROM `tvshow` ORDER BY `name`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $tvshow_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_("Add New") . "...</option>\n";
    }

    echo "</select>\n";
} // show_tvshow_select

/**
 * @param string $name
 * @param $season_id
 * @param boolean $allow_add
 * @param integer $video_id
 * @param boolean $allow_none
 * @return boolean
 */
function show_tvshow_season_select($name, $season_id, $allow_add = false, $video_id = 0, $allow_none = false)
{
    if (!$season_id) {
        return false;
    }
    $season = new TVShow_Season($season_id);

    static $season_id_cnt = 0;
    // Generate key to use for HTML element ID
    if ($video_id) {
        $key = $name . "_select_" . $video_id;
    } else {
        $key = $name . "_select_c" . ++$season_id_cnt;
    }

    echo "<select name=\"$name\" id=\"$key\">\n";

    if ($allow_none) {
        echo "\t<option value=\"-2\"></option>\n";
    }

    $sql        = "SELECT `id`, `season_number` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`";
    $db_results = Dba::read($sql, array($season->tvshow));

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $season_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['season_number']) . "</option>\n";
    } // end while

    if ($allow_add) {
        // Append additional option to the end with value=-1
        echo "\t<option value=\"-1\">" . T_("Add New") . "...</option>\n";
    }

    echo "</select>\n";

    return true;
}

/**
 * show_catalog_select
 * Yet another one of these buggers. this shows a drop down of all of your
 * catalogs.
 * @param string $name
 * @param integer $catalog_id
 * @param string $style
 * @param boolean $allow_none
 * @param string $filter_type
 */
function show_catalog_select($name, $catalog_id, $style = '', $allow_none = false, $filter_type = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";

    $params = array();
    $sql    = "SELECT `id`, `name` FROM `catalog` ";
    if (!empty($filter_type)) {
        $sql .= "WHERE `gather_types` = ?";
        $params[] = $filter_type;
    }
    $sql .= "ORDER BY `name`";
    $db_results = Dba::read($sql, $params);

    if ($allow_none) {
        echo "\t<option value=\"-1\">" . T_('None') . "</option>\n";
    }

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == (string) $catalog_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected>" . scrub_out($row['name']) . "</option>\n";
    } // end while

    echo "</select>\n";
} // show_catalog_select

/**
 * show_album_select
 * This displays a select of every album that we've got in Ampache (which can be
 * hella long). It's used by the Edit page and takes a $name and a $album_id
 * @param string $name
 * @param integer $license_id
 * @param integer $song_id
 */
function show_license_select($name, $license_id = 0, $song_id = 0)
{
    static $license_id_cnt = 0;

    // Generate key to use for HTML element ID
    if ($song_id > 0) {
        $key = "license_select_" . $song_id;
    } else {
        $key = "license_select_c" . ++$license_id_cnt;
    }

    // Added ID field so we can easily observe this element
    echo "<select name=\"$name\" id=\"$key\">\n";

    $sql        = "SELECT `id`, `name`, `description`, `external_link` FROM `license` ORDER BY `name`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $selected = '';
        if ($row['id'] == $license_id) {
            $selected = "selected=\"selected\"";
        }

        echo "\t<option value=\"" . $row['id'] . "\" $selected";
        if (!empty($row['description'])) {
            echo " title=\"" . addslashes($row['description']) . "\"";
        }
        if (!empty($row['external_link'])) {
            echo " data-link=\"" . $row['external_link'] . "\"";
        }
        echo ">" . $row['name'] . "</option>\n";
    } // end while

    echo "</select>\n";
    echo "<a href=\"javascript:show_selected_license_link('" . $key . "');\">" . T_('View License') . "</a>";
} // show_license_select

/**
 * show_user_select
 * This one is for users! shows a select/option statement so you can pick a user
 * to blame
 * @param string $name
 * @param string $selected
 * @param string $style
 */
function show_user_select($name, $selected = '', $style = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";
    echo "\t<option value=\"\">" . T_('All') . "</option>\n";

    $sql        = "SELECT `id`, `username`, `fullname` FROM `user` ORDER BY `fullname`";
    $db_results = Dba::read($sql);

    while ($row = Dba::fetch_assoc($db_results)) {
        $select_txt = '';
        if ($row['id'] == $selected) {
            $select_txt = 'selected="selected"';
        }
        // If they don't have a full name, revert to the username
        $row['fullname'] = $row['fullname'] ? $row['fullname'] : $row['username'];

        echo "\t<option value=\"" . $row['id'] . "\" $select_txt>" . scrub_out($row['fullname']) . "</option>\n";
    } // end while users

    echo "</select>\n";
} // show_user_select

/**
 * show_playlist_select
 * This one is for playlists!
 * @param string $name
 * @param string $selected
 * @param string $style
 */
function show_playlist_select($name, $selected = '', $style = '')
{
    echo "<select name=\"$name\" style=\"$style\">\n";
    echo "\t<option value=\"\">" . T_('None') . "</option>\n";

    $sql              = "SELECT `id`, `name` FROM `playlist` ORDER BY `name`";
    $db_results       = Dba::read($sql);
    $nb_items         = Dba::num_rows($db_results);
    $index            = 1;
    $already_selected = false;

    while ($row = Dba::fetch_assoc($db_results)) {
        $select_txt = '';
        if (!$already_selected && ($row['id'] == $selected || $index == $nb_items)) {
            $select_txt       = 'selected="selected"';
            $already_selected = true;
        }

        echo "\t<option value=\"" . $row['id'] . "\" $select_txt>" . scrub_out($row['name']) . "</option>\n";
        ++$index;
    } // end while users

    echo "</select>\n";
} // show_playlist_select

function xoutput_headers()
{
    $output = (Core::get_request('xoutput') !== '') ? Core::get_request('xoutput') : 'xml';
    if ($output == 'xml') {
        header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        header("Content-Disposition: attachment; filename=ajax.xml");
    } else {
        header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
    }

    header("Expires: Tuesday, 27 Mar 1984 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
}

/**
 * @param array $array
 * @param boolean $callback
 * @param string $type
 * @return false|mixed|string
 */
function xoutput_from_array($array, $callback = false, $type = '')
{
    $output = (Core::get_request('xoutput') !== '') ? Core::get_request('xoutput') : 'xml';
    if ($output == 'xml') {
        return XML_Data::output_xml_from_array($array, $callback, $type);
    } elseif ($output == 'raw') {
        $outputnode = Core::get_request('xoutputnode');

        return $array[$outputnode];
    } else {
        return json_from_array($array, $callback, $type);
    }
}

/**
 * @param $array
 * @param boolean $callback
 * @param string $type
 * @return false|string
 */
function json_from_array($array, $callback = false, $type = '')
{
    return json_encode($array);
}

/**
 * xml_get_header
 * This takes the type and returns the correct xml header
 * @param string $type
 * @return string
 */
function xml_get_header($type)
{
    switch ($type) {
        case 'itunes':
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                "<!DOCTYPE plist PUBLIC \"-//Apple Computer//DTD PLIST 1.0//EN\"\n" .
                "\"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n" .
                "<plist version=\"1.0\">\n" .
                "<dict>\n" .
                "       <key>Major Version</key><integer>1</integer>\n" .
                "       <key>Minor Version</key><integer>1</integer>\n" .
                "       <key>Application Version</key><string>7.0.2</string>\n" .
                "       <key>Features</key><integer>1</integer>\n" .
                "       <key>Show Content Ratings</key><true/>\n" .
                "       <key>Tracks</key>\n" .
                "       <dict>\n";
        case 'xspf':
            return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
            "<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->";
        default:
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    }
} // xml_get_header

/**
 * xml_get_footer
 * This takes the type and returns the correct xml footer
 * @param string $type
 * @return string
 */
function xml_get_footer($type)
{
    switch ($type) {
        case 'itunes':
            return "      </dict>\n" .
                "</dict>\n" .
                "</plist>\n";
        case 'xspf':
            return "      </trackList>\n" .
                "</playlist>\n";
        default:
            return '';
    }
} // xml_get_footer

/**
 * toggle_visible
 * This is identical to the javascript command that it actually calls
 * @param $element
 */
function toggle_visible($element)
{
    echo '<script>';
    echo "toggleVisible('$element');";
    echo "</script>\n";
} // toggle_visible

/**
 * display_notification
 * Show a javascript notification to the user
 * @param string $message
 * @param integer $timeout
 */
function display_notification($message, $timeout = 5000)
{
    echo "<script";
    echo "displayNotification('" . addslashes(json_encode($message, JSON_UNESCAPED_UNICODE)) . "', " . $timeout . ");";
    echo "</script>\n";
}

/**
 * print_bool
 * This function takes a boolean value and then prints out a friendly text
 * message.
 * @param $value
 * @return string
 */
function print_bool($value)
{
    if ($value) {
        $string = '<span class="item_on">' . T_('On') . '</span>';
    } else {
        $string = '<span class="item_off">' . T_('Off') . '</span>';
    }

    return $string;
} // print_bool

/**
 * show_now_playing
 * This shows the Now Playing templates and does some garbage collection
 * this should really be somewhere else
 */
function show_now_playing()
{
    Session::garbage_collection();
    Stream::garbage_collection();

    $web_path = AmpConfig::get('web_path');
    $results  = Stream::get_now_playing();
    require_once AmpConfig::get('prefix') . UI::find_template('show_now_playing.inc.php');
} // show_now_playing

/**
 * @param boolean $render
 * @param boolean $force
 */
function show_table_render($render = false, $force = false)
{
    // Include table render javascript only once
    if ($force || !defined('TABLE_RENDERED')) {
        define('TABLE_RENDERED', 1); ?>
        <?php if (isset($render) && $render) { ?>
            <script>sortPlaylistRender();</script>
        <?php
        }
    }
}
