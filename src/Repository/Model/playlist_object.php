<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
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

    public ?string $name = null;

    public ?int $user = null;

    public ?string $username = null;

    public ?string $type = null;

    public ?string $link = null;

    public int $date = 0;

    public ?int $last_count = 0;

    public ?int $last_duration = 0;

    public ?int $last_update = 0;

    private ?string $f_last_update = null;

    private ?string $f_link = null;

    private ?string $f_name = null;

    private ?string $f_type = null;

    private ?bool $has_art = null;

    /**
     * @return list<array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int
     * }>
     */
    abstract public function get_items(): array;

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
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
     * has_collaborate
     * This function returns true or false if the current user
     * has access to collaborate (Add/remove items) for this playlist
     * @param User|null $user
     */
    public function has_collaborate($user = null): bool
    {
        if ($this->has_access($user)) {
            return true;
        }

        // only playlists have collaborative users
        if ($this instanceof Search) {
            return false;
        }

        $user = ($user instanceof User)
            ? $user
            : Core::get_global('user');

        if (
            $user instanceof User &&
            !empty($this->collaborate) &&
            in_array($user->getId(), explode(',', (string)$this->collaborate))
        ) {
            return true;
        }

        return false;
    }

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     * @param User|null $user
     */
    public function has_access($user = null): bool
    {
        if (
            $user instanceof User &&
            (
                $user->access === AccessLevelEnum::ADMIN->value ||
                $this->user === $user->getId()
            )
        ) {
            return true;
        }

        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) {
            return true;
        }

        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
            return false;
        }

        return (
            Core::get_global('user') instanceof User &&
            $this->user == Core::get_global('user')->id
        );
    }

    /**
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        if ($filter_type) {
            return array_filter(
                $this->get_items(),
                static fn (array $item): bool => $item['object_type']->value === $filter_type
            );
        } else {
            return $this->get_items();
        }
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [];
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if ($this->f_name === null) {
            $show_fullname = AmpConfig::get('show_playlist_username');
            $my_playlist   = (Core::get_global('user') instanceof User && ($this->user == Core::get_global('user')->id));
            $this->f_name  = ($my_playlist || !$show_fullname)
                ? $this->name
                : $this->name . " (" . $this->username . ")";
        }

        return $this->f_name;
    }

    public function getFullname(): string
    {
        return scrub_out($this->get_fullname());
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get_web_path('/client');
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
        if ($this->f_link === null) {
            $link_text    = $this->getFullname();
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . $link_text . '">' . $link_text . '</a>';
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item type (public / private).
     */
    public function get_f_type(): string
    {
        // don't do anything if it's formatted
        if ($this->f_type === null) {
            $this->f_type = ($this->type == 'private') ? Ui::get_material_symbol('lock', T_('Private')) : '';
        }

        return $this->f_type;
    }

    /**
     * Get item update date of the playlist
     */
    public function get_f_last_update(): string
    {
        // don't do anything if it's formatted
        if ($this->f_last_update === null) {
            $this->f_last_update = ($this->last_update)
                ? get_datetime((int)$this->last_update)
                : T_('Unknown');
        }

        return $this->f_last_update;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_childrens(): array
    {
        return $this->get_items();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        debug_event('playlist_object.abstract', 'get_children ' . $name, 5);

        return [];
    }

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
        $images   = [];
        $title    = T_('Playlist Items');
        $web_path = AmpConfig::get_web_path('/client');
        shuffle($medias);
        foreach ($medias as $media) {
            if ($count >= $limit) {
                return $images;
            }

            if (InterfaceImplementationChecker::is_library_item($media['object_type']->value)) {
                if (!Art::has_db($media['object_id'], $media['object_type']->value)) {
                    $className = ObjectTypeToClassNameMapper::map($media['object_type']->value);
                    /** @var playable_item $libitem */
                    $libitem   = new $className($media['object_id']);
                    $parent    = $libitem->get_parent();
                    if ($parent !== null) {
                        $media = $parent;
                    }
                }

                $art = new Art($media['object_id'], $media['object_type']->value);
                if ($art->has_db_info()) {
                    $link     = $web_path . "/image.php?object_id=" . $media['object_id'] . "&object_type=" . $media['object_type']->value;
                    $images[] = ['url' => $link, 'mime' => $art->raw_mime, 'title' => $title];
                }
            }

            ++$count;
        }

        return $images;
    }
}
