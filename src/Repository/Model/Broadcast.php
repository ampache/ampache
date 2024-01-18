<?php

declare(strict_types=0);

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

use Ampache\Module\Util\Ui;
use Ampache\Module\Api\Ajax;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use PDOStatement;

class Broadcast extends database_object implements library_item
{
    protected const DB_TABLENAME = 'broadcast';

    public int $id = 0;
    public int $user;
    public ?string $name;
    public ?string $description;
    public bool $is_private;
    public int $song;
    public bool $started;
    public int $listeners;
    public ?string $key;

    public ?string $link = null;
    /**
     * @var int $song_position
     */
    public $song_position;
    /**
     * @var array $tags
     */
    public $tags;
    /**
     * @var null|string $f_name
     */
    public $f_name;
    /**
     * @var null|string $f_link
     */
    public $f_link;
    /**
     * @var null|string $f_tags
     */
    public $f_tags;

    /**
     * Constructor
     * @param int|null $broadcast_id
     */
    public function __construct($broadcast_id = 0)
    {
        if (!$broadcast_id) {
            return;
        }
        $info = $this->get_info($broadcast_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Update broadcast state.
     * @param bool $started
     * @param string $key
     */
    public function update_state($started, $key = ''): void
    {
        $sql = "UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = '0', `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($started, $key, $this->id));

        $this->started = $started;
    }

    /**
     * Update broadcast listeners.
     * @param int $listeners
     */
    public function update_listeners($listeners): void
    {
        $sql = "UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?";
        Dba::write($sql, array($listeners, $this->id));
        $this->listeners = $listeners;
    }

    /**
     * Update broadcast current song.
     * @param int $song_id
     */
    public function update_song($song_id): void
    {
        $sql = "UPDATE `broadcast` SET `song` = ? WHERE `id` = ?";
        Dba::write($sql, array($song_id, $this->id));
        $this->song          = $song_id;
        $this->song_position = 0;
    }

    /**
     * Delete the broadcast.
     * @return PDOStatement|bool
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
     */
    public static function create($name, $description = ''): int
    {
        if (!empty($name)) {
            $sql    = "INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, '1')";
            $params = array(Core::get_global('user')->id, $name, $description);
            Dba::write($sql, $params);

            return (int)Dba::insert_id();
        }

        return 0;
    }

    /**
     * Update a broadcast from data array.
     * @param array $data
     */
    public function update(array $data): int
    {
        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'broadcast', $this->id, true);
        }
        $name        = $data['title'] ?? $this->name;
        $description = $data['description'] ?? null;
        $private     = !empty($data['private']);

        $sql    = "UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?";
        $params = array($name, $description, $private, $this->id);
        Dba::write($sql, $params);

        return $this->id;
    }

    /**
     * @param bool $details
     */
    public function format($details = true): void
    {
        $this->get_f_link();
        if ($details) {
            $this->tags   = Tag::get_top_tags('broadcast', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'broadcast');
        }
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords(): array
    {
        return array();
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        if (!isset($this->f_name)) {
            $this->f_name = $this->name;
        }

        return $this->f_name;
    }

    /**
     * get_link
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . '/broadcast.php?id=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = '<a href="' . $this->get_link() . '">' . scrub_out($this->get_fullname()) . '</a>';
        }

        return $this->f_link;
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
     * Get item childrens.
     * @return array
     */
    public function get_childrens(): array
    {
        return array();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null): array
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
     * Get item's owner.
     * @return int|null
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
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
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('broadcast', $this->id, (string)$this->get_fullname(), $thumb);
        }
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'broadcast');
    }

    /**
     * Generate a new broadcast key.
     */
    public static function generate_key(): string
    {
        // Should be improved for security reasons!
        return md5(uniqid((string)rand(), true));
    }

    /**
     * Get broadcast from its key.
     * @param string $key
     * @return Broadcast|null
     */
    public static function get_broadcast($key): ?Broadcast
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
    public function show_action_buttons(): void
    {
        if ($this->id) {
            if ((!empty(Core::get_global('user')) && Core::get_global('user')->has_access(75))) {
                echo "<a id=\"edit_broadcast_ " . $this->id . "\" onclick=\"showEditDialog('broadcast_row', '" . $this->id . "', 'edit_broadcast_" . $this->id . "', '" . T_('Broadcast Edit') . "', 'broadcast_row_')\">" . Ui::get_icon('edit', T_('Edit')) . "</a>";
                echo " <a href=\"" . AmpConfig::get('web_path') . "/broadcast.php?action=show_delete&id=" . $this->id . "\">" . Ui::get_icon('delete', T_('Delete')) . "</a>";
            }
        }
    }

    /**
     * Get broadcast link.
     */
    public static function get_broadcast_link(): string
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= "<a href=\"#\" onclick=\"showBroadcastsDialog(event);\">" . Ui::get_icon('broadcast', T_('Broadcast')) . "</a>";
        $link .= "</div>";

        return $link;
    }

    /**
     * Get unbroadcast link.
     * @param int $broadcast_id
     */
    public static function get_unbroadcast_link($broadcast_id): string
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= Ajax::button(
            '?page=player&action=unbroadcast&broadcast_id=' . $broadcast_id,
            'broadcast',
            T_('Unbroadcast'),
            'broadcast_action'
        );
        $link .= "</div>";
        $link .= "<div class=\"broadcast-info\">(<span id=\"broadcast_listeners\">0</span>)</div>";

        return $link;
    }

    /**
     * Get broadcasts from a user.
     * @param int $user_id
     * @return int[]
     */
    public static function get_broadcasts($user_id): array
    {
        $sql        = "SELECT `id` FROM `broadcast` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($user_id));

        $broadcasts = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $broadcasts[] = (int)$results['id'];
        }

        return $broadcasts;
    }

    /**
     * Get play url.
     *
     * @param string $additional_params
     * @param string $player
     * @param bool $local
     */
    public function play_url($additional_params = '', $player = '', $local = false): string
    {
        unset($additional_params, $player, $local);

        return (string)$this->id;
    }
}
