<?php
declare(strict_types=0);
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

/**
 * Radio Class
 *
 * This handles the internet radio stuff, that is inserted into live_stream
 * this can include podcasts or what-have-you
 *
 */
class Live_Stream extends database_object implements media, library_item
{
    /* DB based variables */

    /**
     *  @var integer $id
     */
    public $id;
    /**
     *  @var string $name
     */
    public $name;
    /**
     *  @var string $site_url
     */
    public $site_url;
    /**
     *  @var string $url
     */
    public $url;
    /**
     *  @var string $f_link
     */
    public $link;

    /**
     *  @var string $codec
     */
    public $codec;
    /**
     *  @var integer $catalog
     */
    public $catalog;

    /**
     *  @var string $f_name
     */
    public $f_name;

    /**
     *  @var string $f_link
     */
    public $f_link;
    /**
     *  @var string $f_name_link
     */
    public $f_name_link;
    /**
     *  @var string $f_url_link
     */
    public $f_url_link;

    /**
     * Constructor
     * This takes a flagged.id and then pulls in the information for said flag entry
     * @param integer $stream_id
     */
    public function __construct($stream_id)
    {
        $info = $this->get_info($stream_id, 'live_stream');

        // Set the vars
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // constructor

    /**
     * format
     * This takes the normal data from the database and makes it pretty
     * for the users, the new variables are put in f_??? and f_???_link
     * @param boolean $details
     * @return true
     */
    public function format($details = true)
    {
        unset($details); // dead code but called from other format calls
        // Default link used on the rightbar
        $this->f_name         = scrub_out($this->name);
        $this->link           = AmpConfig::get('web_path') . '/radio.php?action=show&radio=' . scrub_out($this->id);
        $this->f_link         = "<a href=\"" . $this->link . "\">" . $this->f_name . "</a>";
        $this->f_name_link    = "<a target=\"_blank\" href=\"" . $this->site_url . "\">" . $this->f_name . "</a>";
        $this->f_url_link     = "<a target=\"_blank\" href=\"" . $this->url . "\">" . $this->url . "</a>";

        return true;
    } // format

    /**
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->name;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'live_stream') {
            $medias[] = array(
                'object_type' => 'live_stream',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * @return null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return null
     */
    public function get_description()
    {
        return null;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'live_stream') || $force) {
            Art::display('live_stream', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * update
     * This is a static function that takes a key'd array for input
     * it depends on a ID element to determine which radio element it
     * should be updating
     * @param array $data
     * @return boolean|integer
     */
    public function update(array $data)
    {
        if (!$data['name']) {
            AmpError::add('general', T_('Name is required'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string) $data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('general', T_('URL is invalid, must be mms:// , https:// or http://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string) $data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        $sql = "UPDATE `live_stream` SET `name` = ?,`site_url` = ?,`url` = ?, codec = ? WHERE `id` = ?";
        Dba::write($sql, array($data['name'], $data['site_url'], $data['url'], strtolower((string) $data['codec']), $this->id));

        return $this->id;
    } // update

    /**
     * create
     * This is a static function that takes a key'd array for input
     * and if everything is good creates the object.
     * @param array $data
     * @return PDOStatement|boolean
     */
    public static function create(array $data)
    {
        // Make sure we've got a name and codec
        if (!strlen((string) $data['name'])) {
            AmpError::add('name', T_('Name is required'));
        }
        if (!strlen((string) $data['codec'])) {
            AmpError::add('codec', T_('Codec is required (e.g. MP3, OGG...)'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string) $data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('url', T_('URL is invalid, must be http:// or https://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string) $data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        // Make sure it's a real catalog
        $catalog = Catalog::create_from_id($data['catalog']);
        if (!$catalog->name) {
            AmpError::add('catalog', T_('Catalog is invalid'));
        }

        if (AmpError::occurred()) {
            return false;
        }

        // If we've made it this far everything must be ok... I hope
        $sql = "INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) " .
            "VALUES (?, ?, ?, ?, ?)";

        return Dba::write($sql, array($data['name'], $data['site_url'], $data['url'], $catalog->id, strtolower((string) $data['codec'])));
    } // create

    /**
     * delete
     * This deletes the current object from the database
     * @return true
     */
    public function delete()
    {
        $sql = "DELETE FROM `live_stream` WHERE `id` = ?";
        Dba::write($sql, array($this->id));

        return true;
    } // delete

    /**
     * get_stream_types
     * This is needed by the media interface
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return array('foreign');
    } // native_stream

    /**
     * play_url
     * This is needed by the media interface
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @param string $sid
     * @param string $force_http
     * @return string
     */
    public function play_url($additional_params = '', $player = null, $local = false, $sid = '', $force_http = '')
    {
        return $this->url . $additional_params;
    } // play_url

    /**
     * @return string
     */
    public function get_stream_name()
    {
        return $this->get_fullname();
    }

    /**
     * get_transcode_settings
     *
     * This will probably never be implemented
     * @param string $target
     * @param string $player
     * @param array $options
     * @return false
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return false;
    }

    /**
     * @param $catalog
     * @return array
     */
    public static function get_all_radios($catalog = null)
    {
        $sql = "SELECT `live_stream`.`id` FROM `live_stream` JOIN `catalog` ON `catalog`.`id` = `live_stream`.`catalog` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "WHERE `catalog`.`enabled` = '1' ";
        }
        $params = array();
        if ($catalog) {
            if (AmpConfig::get('catalog_disable')) {
                $sql .= "AND ";
            }
            $sql .= "`catalog`.`id` = ?";
            $params[] = $catalog;
        }
        $db_results = Dba::read($sql, $params);
        $radios     = array();

        while ($results = Dba::fetch_assoc($db_results)) {
            $radios[] = $results['id'];
        }

        return $radios;
    }

    /**
     * @return mixed|void
     */
    public static function garbage_collection()
    {
        // Do nothing
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // Do nothing
        unset($user, $agent, $location, $date);

        return false;
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }
} // end live_stream.class
