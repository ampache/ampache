<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use PDOStatement;

class Shoutbox
{
    protected const DB_TABLENAME = 'user_shout';

    public int $id = 0;
    public int $user;
    public string $text;
    public int $date;
    public bool $sticky;
    public int $object_id;
    public ?string $object_type;
    public ?string $data;

    /**
     * Constructor
     * This pulls the shoutbox information from the database and returns
     * a constructed object, uses user_shout table
     * @param int|null $shout_id
     */
    public function __construct($shout_id = null)
    {
        if (!$shout_id) {
            return;
        }
        $this->has_info($shout_id);
    } // Constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * has_info
     * does the db call, reads from the user_shout table
     * @param int $shout_id
     */
    private function has_info($shout_id): bool
    {
        $sql        = "SELECT * FROM `user_shout` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($shout_id));
        $data       = Dba::fetch_assoc($db_results);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // has_info

    /**
     * get_object
     * This takes a type and an ID and returns a created object
     * @param string $type
     * @param int $object_id
     * @return library_item|null
     */
    public static function get_object($type, $object_id)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return null;
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        /** @var library_item $object */
        $object = new $className($object_id);

        if ($object->getId() > 0) {
            if ($object instanceof Song || $object instanceof Podcast_Episode || $object instanceof Video) {
                if (!$object->enabled) {
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
    public function get_image(): string
    {
        $image_string = '';
        if (Art::has_db($this->object_id, (string)$this->object_type)) {
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
        return preg_replace('/(\r\n|\n|\r)/', '<br />', $this->text) ?? '';
    }

    public function getDateFormatted(): string
    {
        return get_datetime((int)$this->date);
    }

    /**
     * Returns true if the object is new/unknown
     */
    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * @param bool $details
     * @param bool $jsbuttons
     */
    public function get_display($details = true, $jsbuttons = false): string
    {
        $object = Shoutbox::get_object((string)$this->object_type, $this->object_id);
        if ($object === null) {
            return '';
        }
        $img    = $this->get_image();
        $html   = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if ($details && $img) {
            $html .= "<div class='shoutbox-img'>" . $img . "</div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->get_f_link() . "</div>";
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
                $html .= "<a href=\"" . AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=" . $this->object_type . "&id=" . $this->object_id . "\">" . Ui::get_icon('comment', T_('Post Shout')) . "</a>";
            }
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>" . T_('by') . " ";

        if ($this->user > 0) {
            $user = new User($this->user);
            if ($details) {
                $html .= $user->get_f_link();
            } else {
                $html .= $user->getUsername();
            }
        } else {
            $html .= T_('Guest');
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
