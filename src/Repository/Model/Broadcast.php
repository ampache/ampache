<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;

class Broadcast extends database_object implements library_item, displayable_item, container_item
{
    protected const string DB_TABLENAME = 'broadcast';

    public ?string $description = null;
    public int $id              = 0;
    public bool $is_private;
    public ?string $key  = null;
    public ?string $link = null;
    public int $listeners;
    public ?string $name = null;
    public int $song;
    public int $song_position = 0;
    public int $started       = 0;
    public int $user;
    private ?string $f_link = null;

    /** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    private ?array $tags = null;

    public function __construct(?int $broadcast_id = 0)
    {
        if (!$broadcast_id) {
            return;
        }

        $info = $this->get_info($broadcast_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Create a broadcast
     */
    public static function create(string $name, string $description = ''): int
    {
        if (!empty($name)) {
            $sql    = "INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, '1')";
            $params = [Core::get_global('user')?->getId(), $name, $description];
            Dba::write($sql, $params);

            return (int)Dba::insert_id();
        }

        return 0;
    }

    /**
     * Get broadcast from its key.
     */
    public static function get_broadcast(string $key): ?Broadcast
    {
        $sql        = "SELECT `id` FROM `broadcast` WHERE `key` = ?";
        $db_results = Dba::read($sql, [$key]);

        if ($results = Dba::fetch_assoc($db_results)) {
            return new Broadcast($results['id']);
        }

        return null;
    }

    /**
     * Get broadcast link.
     */
    public static function get_broadcast_link(): string
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= "<a href=\"#\" onclick=\"showBroadcastsDialog(event);\">" . Ui::get_material_symbol('cell_tower', T_('Broadcast')) . "</a>";

        return $link . "</div>";
    }

    /**
     * Get broadcasts from a user.
     * @return int[]
     */
    public static function get_broadcasts(int $user_id): array
    {
        $sql        = "SELECT `id` FROM `broadcast` WHERE `user` = ?";
        $db_results = Dba::read($sql, [$user_id]);

        $broadcasts = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $broadcasts[] = (int)$results['id'];
        }

        return $broadcasts;
    }

    /**
     * Get unbroadcast link.
     */
    public static function get_unbroadcast_link(int $broadcast_id): string
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= Ajax::button(
            '?page=player&action=unbroadcast&broadcast_id=' . $broadcast_id,
            'cell_tower',
            T_('Unbroadcast'),
            'broadcast_action'
        );
        $link .= "</div>";

        return $link . "<div class=\"broadcast-info\">(<span id=\"broadcast_listeners\">0</span>)</div>";
    }

    /**
     * Delete the broadcast.
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM `broadcast` WHERE `id` = ?";

        return (Dba::write($sql, [$this->id]) !== null);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('broadcast', $this->id, (string)$this->get_fullname(), $size);
        }
    }

    /**
     * Get default art kind for this item.
     */
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
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = '<a href="' . $this->get_link() . '">' . scrub_out($title ?? $this->get_fullname()) . '</a>';
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
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'broadcast');
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        return $this->name;
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
     * get_link
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . '/broadcast.php?id=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get all childrens and sub-childrens medias.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string  $filter_type = null): array
    {
        // Not a media, shouldn't be that
        $medias = [];
        if ($filter_type === null || $filter_type === 'broadcast') {
            $medias[] = ['object_type' => LibraryItemEnum::BROADCAST, 'object_id' => $this->id];
        }

        return $medias;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_parent_fullname(): string
    {
        return '';
    }

    /**
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('broadcast', $this->id);
        }

        return $this->tags ?? [];
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::BROADCAST;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'broadcast');
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Show action buttons.
     */
    public function show_action_buttons(): void
    {
        if ($this->id !== 0 && (Core::get_global('user') instanceof User && Core::get_global('user')->has_access(AccessLevelEnum::MANAGER))) {
            echo "<a id=\"edit_broadcast_ " . $this->id . "\" onclick=\"showEditDialog('broadcast_row', '" . $this->id . "', 'edit_broadcast_" . $this->id . "', '" . T_('Broadcast Edit') . "', 'broadcast_row_')\">" . Ui::get_material_symbol('edit', T_('Edit')) . "</a>";
            echo " <a href=\"" . AmpConfig::get_web_path() . "/broadcast.php?action=show_delete&id=" . $this->id . "\">" . Ui::get_material_symbol('close', T_('Delete')) . "</a>";
        }
    }

    /**
     * Update a broadcast from data array.
     */
    public function update(array $data): int
    {
        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'broadcast', $this->id, true);
        }

        $name        = $data['title'] ?? $this->name;
        $description = $data['description'] ?? '';
        $private     = (!empty($data['private'] && (int)$data['private'] === 1)) ? 1 : 0;

        $sql    = "UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?";
        $params = [$name, $description, $private, $this->id];
        Dba::write($sql, $params);

        return $this->id;
    }

    /**
     * Update broadcast listeners.
     */
    public function update_listeners(int $listeners): void
    {
        $sql = "UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?";
        Dba::write($sql, [$listeners, $this->id]);
        $this->listeners = $listeners;
    }

    /**
     * Update broadcast current song.
     */
    public function update_song(int $song_id): void
    {
        $sql = "UPDATE `broadcast` SET `song` = ? WHERE `id` = ?";
        Dba::write($sql, [$song_id, $this->id]);
        $this->song          = $song_id;
        $this->song_position = 0;
    }

    /**
     * Update broadcast state.
     */
    public function update_state(int $started, string $key = ''): void
    {
        $sql = "UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = '0', `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, [$started, $key, $this->id]);

        $this->started = $started;
    }
}
