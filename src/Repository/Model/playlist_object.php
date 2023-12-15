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
    public int $id = 0;
    public ?string $name;
    public ?int $user;
    public ?string $username;
    public ?string $type;

    public ?string $link       = null;
    public int $date           = 0;
    public ?int $last_duration = 0;
    public ?int $last_update   = 0;
    public ?string $f_date;
    public ?string $f_last_update;
    public ?string $f_link;
    public ?string $f_type;
    public ?string $f_name;

    private ?bool $has_art = null;

    /**
     * @return array
     */
    abstract public function get_items();

    /**
     * format
     * This takes the current playlist object and gussies it up a little bit so it is presentable to the users
     * @param bool $details
     */
    public function format($details = true): void
    {
        // format shared lists using the username
        $this->f_name = (!empty(Core::get_global('user')) && ($this->user == Core::get_global('user')->id))
            ? scrub_out($this->name)
            : scrub_out($this->name . " (" . $this->username . ")");
        $this->get_f_type();
        $this->get_f_link();
        $this->f_date        = $this->date ? get_datetime((int)$this->date) : T_('Unknown');
        $this->f_last_update = $this->last_update ? get_datetime((int)$this->last_update) : T_('Unknown');
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = ($this instanceof Search)
                ? Art::has_db($this->id, 'search')
                : Art::has_db($this->id, 'playlist');
        }

        return $this->has_art;
    }

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     * @param int $user_id
     */
    public function has_access($user_id = null): bool
    {
        if (Access::check('interface', 100)) {
            return true;
        }
        if (!Access::check('interface', 25)) {
            return false;
        }
        // allow the owner
        if ((!empty(Core::get_global('user')) && $this->user == Core::get_global('user')->id) || ($this->user == $user_id)) {
            return true;
        }

        return false;
    }

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
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        $show_fullname = AmpConfig::get('show_playlist_username');
        $my_playlist   = (!empty(Core::get_global('user')) && ($this->user == Core::get_global('user')->id));
        $this->f_name  = ($my_playlist || !$show_fullname)
            ? $this->name
            : $this->name . " (" . $this->username . ")";

        return $this->f_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = ($this instanceof Search)
                ? $web_path . '/smartplaylist.php?action=show&playlist_id=' . $this->id
                : $web_path . '/playlist.php?action=show&playlist_id=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $link_text    = scrub_out($this->get_fullname());
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . $link_text . '">' . $link_text . '</a>';
        }

        return $this->f_link;
    }

    /**
     * Get item type (public / private).
     */
    public function get_f_type(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_type)) {
            $this->f_type = ($this->type == 'private') ? Ui::get_icon('lock', T_('Private')) : '';
        }

        return $this->f_type;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return $this->get_items();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name)
    {
        debug_event('playlist_object.abstract', 'get_children ' . $name, 5);

        return array();
    }

    /**
     * @return int|null
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        return '';
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     * @param bool $link
     */
    public function display_art($thumb = 2, $force = false, $link = true): void
    {
        if (AmpConfig::get('playlist_art') || $force) {
            $add_link  = ($link) ? $this->get_link() : null;
            $list_type = ($this instanceof Search)
                ? 'search'
                : 'playlist';
            Art::display($list_type, $this->id, (string)$this->get_fullname(), $thumb, $add_link);
        }
    }

    /**
     * gather_art
     */
    public function gather_art($limit): array
    {
        $medias   = $this->get_medias();
        $count    = 0;
        $images   = array();
        $title    = T_('Playlist Items');
        $web_path = AmpConfig::get('web_path');
        shuffle($medias);
        foreach ($medias as $media) {
            if ($count >= $limit) {
                return $images;
            }
            if (InterfaceImplementationChecker::is_library_item($media['object_type'])) {
                if (!Art::has_db($media['object_id'], $media['object_type'])) {
                    $className = ObjectTypeToClassNameMapper::map($media['object_type']);
                    $libitem   = new $className($media['object_id']);
                    $parent    = $libitem->get_parent();
                    if ($parent !== null) {
                        $media = $parent;
                    }
                }
                $art = new Art($media['object_id'], $media['object_type']);
                if ($art->has_db_info()) {
                    $link     = $web_path . "/image.php?object_id=" . $media['object_id'] . "&object_type=" . $media['object_type'];
                    $images[] = ['url' => $link, 'mime' => $art->raw_mime, 'title' => $title];
                }
            }
            $count++;
        }

        return $images;
    }
}
