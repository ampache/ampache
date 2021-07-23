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

namespace Ampache\Repository\Model;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/**
 * playlist_object
 * Abstracting out functionality needed by both normal and smart playlists
 */
abstract class playlist_object extends database_object implements library_item
{
    // Database variables
    /**
     * @var integer $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var integer $user
     */
    public $user;
    /**
     * @var string $type
     */
    public $type;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_type
     */
    public $f_type;
    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $f_user
     */
    public $f_user;

    /**
     * @return array
     */
    abstract public function get_items();

    /**
     * format
     * This takes the current playlist object and gussies it up a little
     * bit so it is presentable to the users
     * @param boolean $details
     */
    public function format($details = true)
    {
        $this->f_name = $this->name;
        $this->f_type = ($this->type == 'private') ? Ui::get_icon('lock', T_('Private')) : '';

        if ($details) {
            $client = new User($this->user);
            $client->format();
            $this->f_user = $client->f_name;
        }
    } // format

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     * @param integer $user_id
     * @return boolean
     */
    public function has_access($user_id = null)
    {
        if (!Access::check('interface', 25)) {
            return false;
        }
        if (Access::check('interface', 100)) {
            return true;
        }
        // allow the owner
        if (($this->user == Core::get_global('user')->id) || ($this->user == $user_id)) {
            return true;
        }

        return false;
    } // has_access

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = $this->get_items();
        if ($filter_type) {
            $nmedias = array();
            foreach ($medias as $media) {
                if ($media['object_type'] == $filter_type) {
                    $nmedias[] = $media;
                }
            }
            $medias = $nmedias;
        }

        return $medias;
    }

    /**
     * @return array|mixed
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
        return $this->f_name;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return mixed
     */
    public function get_childrens()
    {
        $childrens = array();
        $items     = $this->get_items();
        foreach ($items as $item) {
            if (!in_array($item['object_type'], $childrens)) {
                $childrens[$item['object_type']] = array();
            }
            $childrens[$item['object_type']][] = $item['object_id'];
        }

        return $this->get_items();
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event('playlist_object.abstract', 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @return integer
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
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
        if (AmpConfig::get('playlist_art') || $force) {
            $medias     = $this->get_medias();
            shuffle($medias);
            foreach ($medias as $media) {
                if (InterfaceImplementationChecker::is_library_item($media['object_type'])) {
                    if (!Art::has_db($media['object_id'], $media['object_type'])) {
                        $class_name = ObjectTypeToClassNameMapper::map($media['object_type']);
                        $libitem    = new $class_name($media['object_id']);
                        $parent     = $libitem->get_parent();
                        if ($parent !== null) {
                            $media = $parent;
                        } elseif (!$force) {
                            $media = null;
                        }
                    }

                    if ($media !== null) {
                        Art::display($media['object_type'], $media['object_id'], $this->get_fullname(), $thumb, $this->link);

                        return;
                    }
                }
            }
        }
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
} // end playlist_object.class
