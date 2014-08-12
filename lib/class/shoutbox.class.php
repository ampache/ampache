<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

class Shoutbox
{
    public $id;
    public $object_type;
    public $object_id;
    public $user;
    public $sticky;
    public $text;
    public $data;
    public $date;

    public $f_link;

    /**
     * Constructor
     * This pulls the shoutbox information from the database and returns
     * a constructed object, uses user_shout table
     */
    public function __construct($shout_id)
    {
        // Load the data from the database
        $this->_get_info($shout_id);

        return true;

    } // Constructor

    /**
     * _get_info
     * does the db call, reads from the user_shout table
     */
    private function _get_info($shout_id)
    {
        $sql = "SELECT * FROM `user_shout` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($shout_id));

        $data = Dba::fetch_assoc($db_results);

        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }

        return true;

    } // _get_info

    /**
     * gc
     *
     * Cleans out orphaned shoutbox items
     */
    public static function gc()
    {
        foreach (array('song', 'album', 'artist') as $object_type) {
            Dba::write("DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `$object_type` ON `$object_type`.`id` = `user_shout`.`object_id` WHERE `$object_type`.`id` IS NULL AND `user_shout`.`object_type` = '$object_type'");
        }
    }

    /**
     * get_top
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     */
    public static function get_top($limit)
    {
        $shouts = self::get_sticky();

        // If we've already got too many stop here
        if (count($shouts) > $limit) {
            $shouts = array_slice($shouts,0,$limit);
            return $shouts;
        }

        // Only get as many as we need
        $limit = intval($limit) - count($shouts);
        $sql = "SELECT * FROM `user_shout` WHERE `sticky`='0' ORDER BY `date` DESC LIMIT $limit";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = $row['id'];
        }

        return $shouts;

    } // get_top

    public static function get_shouts_since($time)
    {
        $sql = "SELECT * FROM `user_shout` WHERE `date` > ? ORDER BY `date` DESC";
        $db_results = Dba::read($sql, array($time));

        $shouts = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = $row['id'];
        }

        return $shouts;

    }

    /**
     * get_sticky
     * This returns all current sticky shoutbox items
     */
    public static function get_sticky()
    {
        $sql = "SELECT * FROM `user_shout` WHERE `sticky`='1' ORDER BY `date` DESC";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;

    } // get_sticky

    /**
     * get_object
     * This takes a type and an ID and returns a created object
     */
    public static function get_object($type,$object_id)
    {
        if (!Core::is_library_item($type))
            return false;

        $object = new $type($object_id);

        return $object;

    } // get_object

    /**
     * get_image
     * This returns an image tag if the type of object we're currently rolling with
     * has an image associated with it
     */
    public function get_image()
    {
        $image_string = '';
        if (Art::has_db($this->object_id, $this->object_type)) {
            $image_string = "<img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . AmpConfig::get('web_path') . "/image.php?object_id=" . $this->object_id . "&object_type=" . $this->object_type . "&thumb=1\" />";
        }

        return $image_string;

    } // get_image

    /**
     * create
     * This takes a key'd array of data as input and inserts a new shoutbox entry, it returns the auto_inc id
     */
    public static function create(array $data)
    {
        $sticky     = isset($data['sticky']) ? 1 : 0;
        $sql = "INSERT INTO `user_shout` (`user`,`date`,`text`,`sticky`,`object_id`,`object_type`, `data`) " .
            "VALUES (? , ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($GLOBALS['user']->id, time(), strip_tags($data['comment']), $sticky, $data['object_id'], $data['object_type'], $data['data']));

        $insert_id = Dba::insert_id();

        return $insert_id;

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a shoutbox entry
     */
    public function update(array $data)
    {
        $sql = "UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['comment'], make_bool($data['sticky']), $this->id));

        return $this->id;

    } // create

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        $this->sticky = ($this->sticky == "0") ? 'No' : 'Yes';
        $this->date = date("m\/d\/Y - H:i", $this->date);
        return true;

    } //format

    /**
     * delete
     * this function deletes a specific shoutbox entry
     */

    public function delete($shout_id)
    {
        // Delete the shoutbox post
        $shout_id = Dba::escape($shout_id);
        $sql = "DELETE FROM `user_shout` WHERE `id`='$shout_id'";
        Dba::write($sql);

    } // delete

    public function get_display($details = true, $jsbuttons = false)
    {
        $object = Shoutbox::get_object($this->object_type, $this->object_id);
        $object->format();
        $user = new User($this->user);
        $user->format();
        $img = $this->get_image();
        $html = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if ($details && $img) {
            $html .= "<div class='shoutbox-img'>" . $img . "</div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->f_link . "</div>";
            $html .= "<div class='shoutbox-date'>".date("Y/m/d H:i:s", $this->date) . "</div>";
        }
        $html .= "<div class='shoutbox-text'>" . preg_replace('/(\r\n|\n|\r)/', '<br />', $this->text) . "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='shoutbox-footer'>";
        if ($details) {
            $html .= "<div class='shoutbox-actions'>";
            if ($jsbuttons) {
                $html .= Ajax::button('?page=stream&action=directplay&playtype=' . $this->object_type .'&' . $this->object_type . '_id=' . $this->object_id,'play', T_('Play'),'play_' . $this->object_type . '_' . $this->object_id);
                $html .= Ajax::button('?action=basket&type=' . $this->object_type .'&id=' . $this->object_id,'add', T_('Add'),'add_' . $this->object_type . '_' . $this->object_id);
            }
            $html .= "<a href=\"" . AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=" . $this->object_type . "&id=" . $this->object_id . "\">" . UI::get_icon('comment', T_('Post Shout')) . "</a>";
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>by ";
        if ($details) {
            $html .= $user->f_link;
        } else {
            $html .= $user->username;
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    public static function get_shouts($object_type, $object_id)
    {
        $sql = "SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC";
        $db_results = Dba::read($sql, array($object_type, $object_id));
        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

} // Shoutbox class
