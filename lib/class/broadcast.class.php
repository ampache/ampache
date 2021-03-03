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

class Broadcast extends database_object implements library_item
{
    /**
     *  @var integer $id
     */
    public $id;
    /**
     *  @var boolean $started
     */
    public $started;
    /**
     *  @var integer $listeners
     */
    public $listeners;
    /**
     *  @var integer $song
     */
    public $song;
    /**
     *  @var integer $song_position
     */
    public $song_position;
    /**
     *  @var string $name
     */
    public $name;
    /**
     *  @var integer $user
     */
    public $user;

    /**
     *  @var array $tags
     */
    public $tags;
    /**
     *  @var string $f_name
     */
    public $f_name;
    /**
     *  @var string $f_link
     */
    public $f_link;
    /**
     *  @var string $f_tags
     */
    public $f_tags;
    /**
     *  @var boolean $is_private
     */
    public $is_private;

    /**
     * Constructor
     * @param integer $broadcast_id
     */
    public function __construct($broadcast_id)
    {
        /* Get the information from the db */
        $info = $this->get_info($broadcast_id);

        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // constructor

    /**
     * Update broadcast state.
     * @param boolean $started
     * @param string $key
     */
    public function update_state($started, $key = '')
    {
        $sql = "UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = '0', `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($started, $key, $this->id));

        $this->started = $started;
    }

    /**
     * Update broadcast listeners.
     * @param integer $listeners
     */
    public function update_listeners($listeners)
    {
        $sql = "UPDATE `broadcast` SET `listeners` = ? " .
            "WHERE `id` = ?";
        Dba::write($sql, array($listeners, $this->id));
        $this->listeners = $listeners;
    }

    /**
     * Update broadcast current song.
     * @param integer $song_id
     */
    public function update_song($song_id)
    {
        $sql = "UPDATE `broadcast` SET `song` = ? " .
            "WHERE `id` = ?";
        Dba::write($sql, array($song_id, $this->id));
        $this->song          = $song_id;
        $this->song_position = 0;
    }

    /**
     * Delete the broadcast.
     * @return PDOStatement|boolean
     */
    public function delete()
    {
        $sql = "DELETE FROM `broadcast` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    /**
     * Create a broadcast
     * @param string $name
     * @param string $description
     * @return integer
     */
    public static function create($name, $description = '')
    {
        if (!empty($name)) {
            $sql    = "INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, '1')";
            $params = array(Core::get_global('user')->id, $name, $description);
            Dba::write($sql, $params);

            return Dba::insert_id();
        }

        return 0;
    }

    /**
     * Update a broadcast from data array.
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'broadcast', $this->id, true);
        }

        $sql = "UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? " .
            "WHERE `id` = ?";
        $params = array($data['name'], $data['description'], !empty($data['private']), $this->id);
        Dba::write($sql, $params);

        return $this->id;
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        $this->f_name = $this->name;
        $this->f_link = '<a href="' . AmpConfig::get('web_path') . '/broadcast.php?id=' . $this->id . '">' . scrub_out($this->f_name) . '</a>';
        if ($details) {
            $this->tags   = Tag::get_top_tags('broadcast', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'broadcast');
        }
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        // Not a media, shouldn't be that
        $medias = array();
        if ($filter_type === null || $filter_type == 'broadcast') {
            $medias[] = array(
                'object_type' => 'broadcast',
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
        return array();
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return mixed|null
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
        if (Art::has_db($this->id, 'broadcast') || $force) {
            Art::display('broadcast', $this->id, $this->get_fullname(), $thumb);
        }
    }

    /**
     * Get all broadcasts sql query.
     * @return string
     */
    public static function get_broadcast_list_sql()
    {
        return "SELECT `id` FROM `broadcast` WHERE `started` = '1' ";
    }

    /**
     * Get all broadcasts.
     * @return integer[]
     */
    public static function get_broadcast_list()
    {
        $sql        = self::get_broadcast_list_sql();
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * Generate a new broadcast key.
     * @return string
     */
    public static function generate_key()
    {
        // Should be improved for security reasons!
        return md5(uniqid((string) rand(), true));
    }

    /**
     * Get broadcast from its key.
     * @param string $key
     * @return Broadcast|null
     */
    public static function get_broadcast($key)
    {
        $sql        = "SELECT `id` FROM `broadcast` WHERE `key` = ?";
        $db_results = Dba::read($sql, array($key));

        if ($results = Dba::fetch_assoc($db_results)) {
            return new Broadcast($results['id']);
        }

        return null;
    }

    /**
     * Show action buttons.
     */
    public function show_action_buttons()
    {
        if ($this->id) {
            if (Core::get_global('user')->has_access('75')) {
                echo "<a id=\"edit_broadcast_ " . $this->id . "\" onclick=\"showEditDialog('broadcast_row', '" . $this->id . "', 'edit_broadcast_" . $this->id . "', '" . T_('Broadcast Edit') . "', 'broadcast_row_')\">" . UI::get_icon('edit', T_('Edit')) . "</a>";
                echo " <a href=\"" . AmpConfig::get('web_path') . "/broadcast.php?action=show_delete&id=" . $this->id . "\">" . UI::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    /**
     * Get broadcast link.
     * @return string
     */
    public static function get_broadcast_link()
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= "<a href=\"#\" onclick=\"showBroadcastsDialog(event);\">" . UI::get_icon('broadcast', T_('Broadcast')) . "</a>";
        $link .= "</div>";

        return $link;
    }

    /**
     * Get unbroadcast link.
     * @param integer $broadcast_id
     * @return string
     */
    public static function get_unbroadcast_link($broadcast_id)
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= Ajax::button('?page=player&action=unbroadcast&broadcast_id=' . $broadcast_id, 'broadcast', T_('Unbroadcast'), 'broadcast_action');
        $link .= "</div>";
        $link .= "<div class=\"broadcast-info\">(<span id=\"broadcast_listeners\">0</span>)</div>";

        return $link;
    }

    /**
     * Get broadcasts from an user.
     * @param integer $user_id
     * @return integer[]
     */
    public static function get_broadcasts($user_id)
    {
        $sql        = "SELECT `id` FROM `broadcast` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($user_id));

        $broadcasts = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $broadcasts[] = $results['id'];
        }

        return $broadcasts;
    }

    /**
     * @return mixed|void
     */
    public static function garbage_collection()
    {
    }

    /**
     * Get play url.
     *
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return integer
     */
    public function play_url($additional_params = '', $player = null, $local = false)
    {
        unset($additional_params, $player, $local);

        return $this->id;
    }
} // end broadcast.class
