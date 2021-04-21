<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;

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

    /**
     * Constructor
     * This pulls the shoutbox information from the database and returns
     * a constructed object, uses user_shout table
     * @param integer $shout_id
     */
    public function __construct($shout_id)
    {
        // Load the data from the database
        $this->has_info($shout_id);

        return true;
    } // Constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * has_info
     * does the db call, reads from the user_shout table
     * @param integer $shout_id
     * @return boolean
     */
    private function has_info($shout_id)
    {
        $sql        = "SELECT * FROM `user_shout` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($shout_id));

        $data = Dba::fetch_assoc($db_results);

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // has_info

    /**
     * get_object
     * This takes a type and an ID and returns a created object
     * @param string $type
     * @param integer $object_id
     * @return Object
     */
    public static function get_object($type, $object_id)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return null;
        }

        $class_name = ObjectTypeToClassNameMapper::map($type);
        $object     = new $class_name($object_id);

        if ($object->id > 0) {
            if (strtolower((string)$type) === 'song') {
                /** @var Song $object */
                if (!$object->isEnabled()) {
                    $object = null;
                }
            }
        } else {
            $object = null;
        }

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

    public function getStickyFormatted(): string
    {
        return $this->sticky == '0' ? 'No' : 'Yes';
    }

    public function getTextFormatted(): string
    {
        return preg_replace('/(\r\n|\n|\r)/', '<br />', $this->text);
    }

    public function getDateFormatted(): string
    {
        return get_datetime((int)$this->date);
    }

    /**
     * @param boolean $details
     * @param boolean $jsbuttons
     * @return string
     */
    public function get_display($details = true, $jsbuttons = false)
    {
        $object = Shoutbox::get_object($this->object_type, $this->object_id);
        $object->format();
        $img  = $this->get_image();
        $html = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if ($details && $img) {
            $html .= "<div class='shoutbox-img'>" . $img . "</div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->f_link . "</div>";
            $html .= "<div class='shoutbox-date'>" . get_datetime((int)$this->date) . "</div>";
        }
        $html .= "<div class='shoutbox-text'>" . $this->getTextFormatted() . "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='shoutbox-footer'>";
        if ($details) {
            $html .= "<div class='shoutbox-actions'>";
            if ($jsbuttons) {
                $html .= Ajax::button('?page=stream&action=directplay&playtype=' . $this->object_type . '&' . $this->object_type . '_id=' . $this->object_id,
                    'play', T_('Play'), 'play_' . $this->object_type . '_' . $this->object_id);
                $html .= Ajax::button('?action=basket&type=' . $this->object_type . '&id=' . $this->object_id, 'add',
                    T_('Add'), 'add_' . $this->object_type . '_' . $this->object_id);
            }
            if (Access::check('interface', 25)) {
                $html .= "<a href=\"" . AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=" . $this->object_type . "&id=" . $this->object_id . "\">" . Ui::get_icon('comment',
                        T_('Post Shout')) . "</a>";
            }
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>" . T_('by') . " ";

        if ($this->user > 0) {
            $user = new User($this->user);
            $user->format();
            if ($details) {
                $html .= $user->f_link;
            } else {
                $html .= $user->username;
            }
        } else {
            $html .= T_('Guest');
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }
}
