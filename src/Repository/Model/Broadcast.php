<?php

declare(strict_types=1);

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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\BroadcastRepositoryInterface;

class Broadcast extends database_object implements library_item, displayable_item, container_item, ModelInterface
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

        $info                = $this->get_info($broadcast_id, static::DB_TABLENAME);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->user          = (int) ($info['user'] ?? 0);
        $this->name          = $info['name'] ?? null;
        $this->description   = $info['description'] ?? null;
        $this->is_private    = (bool) ($info['is_private'] ?? false);
        $this->song          = (int) ($info['song'] ?? 0);
        $this->listeners     = (int) ($info['listeners'] ?? 0);
        $this->key           = $info['key'] ?? null;
        $this->started       = (int) ($info['started'] ?? 0);
        $this->song_position = (int) ($info['song_position'] ?? 0);
    }

    /**
     * Create a broadcast
     */
    public static function create(string $name, string $description = ''): int
    {
        if (!empty($name)) {
            $user = Core::get_global('user');

            // a broadcast requires a session to listen unless the owner turned that off
            return self::getBroadcastRepository()->create(
                (int) $user?->getId(),
                $name,
                $description,
                (bool) ($user?->getPreferenceValue(ConfigurationKeyEnum::BROADCAST_PRIVATE) ?? true)
            );
        }

        return 0;
    }

    /**
     * Get broadcast from its key.
     */
    public static function get_broadcast(string $key): ?Broadcast
    {
        return self::getBroadcastRepository()->findByKey($key);
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
        return self::getBroadcastRepository()->getIdsByUser($user_id);
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
     * @deprecated inject dependency
     */
    private static function getBroadcastRepository(): BroadcastRepositoryInterface
    {
        global $dic;

        return $dic->get(BroadcastRepositoryInterface::class);
    }

    /**
     * Delete the broadcast.
     */
    public function delete(): bool
    {
        try {
            $this->getBroadcastRepository()->delete($this);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('broadcast', $this->id, (string) $this->get_fullname(), $size);
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
     * Persists the object
     *
     * An object that has not been saved yet receives the id its row was given
     */
    public function save(): void
    {
        $result = self::getBroadcastRepository()->persist($this);

        if ($result !== null) {
            $this->id = $result;
        }

        // memory_cache is on by default, so the row this object just wrote has to leave the request cache
        self::remove_from_cache('broadcast', $this->id);
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

        $this->name        = $data['name'] ?? $this->name;
        $this->description = $data['description'] ?? '';
        $this->is_private  = (!empty($data['private']) && (int) $data['private'] === 1);

        $this->getBroadcastRepository()->update($this);

        return $this->id;
    }

    /**
     * Update broadcast listeners.
     */
    public function update_listeners(int $listeners): void
    {
        $this->getBroadcastRepository()->updateListeners($this, $listeners);

        $this->listeners = $listeners;
    }

    /**
     * Update broadcast current song.
     */
    public function update_song(int $song_id): void
    {
        $this->getBroadcastRepository()->updateSong($this, $song_id);

        $this->song          = $song_id;
        $this->song_position = 0;
    }

    /**
     * Update broadcast state.
     */
    public function update_state(int $started, string $key = ''): void
    {
        $this->getBroadcastRepository()->updateState($this, $started, $key);

        $this->started = $started;
    }
}
