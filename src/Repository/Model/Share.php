<?php

declare(strict_types=0);

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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use PDOStatement;

class Share extends database_object
{
    protected const DB_TABLENAME = 'share';

    /** @var list<LibraryItemEnum> */
    public const VALID_TYPES = [
        LibraryItemEnum::ALBUM,
        LibraryItemEnum::ALBUM_DISK,
        LibraryItemEnum::ARTIST,
        LibraryItemEnum::PLAYLIST,
        LibraryItemEnum::PODCAST,
        LibraryItemEnum::PODCAST_EPISODE,
        LibraryItemEnum::SEARCH,
        LibraryItemEnum::SONG,
        LibraryItemEnum::VIDEO,
    ];

    public int $id = 0;

    public int $user;

    public ?string $object_type = null;

    public int $object_id;

    public bool $allow_stream;

    public bool $allow_download;

    public int $expire_days;

    public int $max_counter;

    public ?string $secret = null;

    public int $counter;

    public int $creation_date;

    public int $lastvisit_date;

    public ?string $public_url = null;

    public ?string $description = null;

    public $f_name;

    /** @var Song|Artist|Album|playlist_object|null $object */
    private $object;

    /**
     * Constructor
     * @param int|null $share_id
     */
    public function __construct($share_id = 0)
    {
        if (!$share_id) {
            return;
        }

        $info = $this->get_info($share_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function isNew(): bool
    {
        return $this->id === 0;
    }

    /**
     * get_url
     * @param null|int $share_id
     * @param string $secret
     */
    public static function get_url($share_id, $secret): string
    {
        $url = AmpConfig::get('web_path') . '/share.php?id=' . $share_id;
        if (!empty($secret)) {
            $url .= '&secret=' . $secret;
        }

        return $url;
    }

    /**
     * Returns `true` if the user may access the share item
     */
    public function isAccessible(User $user): bool
    {
        return $user->has_access(AccessLevelEnum::MANAGER) ||
            $this->user === $user->getId();
    }

    public function show_action_buttons(): void
    {
        if (
            $this->isNew() === false &&
            (
                Core::get_global('user') instanceof User &&
                (
                    Core::get_global('user')->has_access(AccessLevelEnum::MANAGER) ||
                    $this->user === Core::get_global('user')->id
                )
            )
        ) {
            if ($this->allow_download) {
                echo "<a class=\"nohtml\" href=\"" . $this->public_url . "&action=download\">" . Ui::get_material_symbol('download', T_('Download')) . "</a>";
            }

            echo "<a id=\"edit_share_ " . $this->id . "\" onclick=\"showEditDialog('share_row', '" . $this->id . "', 'edit_share_" . $this->id . "', '" . T_('Share Edit') . "', 'share_')\">" . Ui::get_material_symbol('edit', T_('Edit')) . "</a>";
            echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=show_delete&id=" . $this->id . "\">" . Ui::get_material_symbol('close', T_('Delete')) . "</a>";
        }
    }

    public function hasObject(): bool
    {
        return $this->getObject() !== null;
    }

    /**
     * @return Song|Artist|Album|playlist_object|null
     */
    private function getObject()
    {
        if ($this->object === null) {
            /** @var Song|Artist|Album|playlist_object|null $object */
            $object = $this->getLibraryItemLoader()->load(
                LibraryItemEnum::from((string) $this->object_type),
                $this->object_id,
                [Song::class, Artist::class, Album::class, playlist_object::class]
            );
            $this->object = $object;
        }

        return $this->object ?? null;
    }

    public function getObjectUrl(): string
    {
        return ($this->getObject())
            ? $this->getObject()->get_f_link()
            : '';
    }

    public function getObjectName(): string
    {
        return ($this->getObject())
            ? (string)$this->getObject()->get_fullname()
            : '';
    }

    public function getUserName(): string
    {
        return User::get_username($this->user);
    }

    public function getLastVisitDateFormatted(): string
    {
        return $this->lastvisit_date > 0 ? get_datetime($this->lastvisit_date) : '';
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime($this->creation_date);
    }

    /**
     * update
     * @return PDOStatement|bool
     */
    public function update(array $data, User $user)
    {
        $this->max_counter    = (int)($data['max_counter']);
        $this->expire_days    = (int)($data['expire']);
        $this->allow_stream   = ($data['allow_stream'] == '1');
        $this->allow_download = ($data['allow_download'] == '1');
        $this->description    = $data['description'] ?? $this->description;

        $sql    = "UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? WHERE `id` = ?";
        $params = [
            $this->max_counter,
            $this->expire_days,
            $this->allow_stream ? 1 : 0,
            $this->allow_download ? 1 : 0,
            $this->description,
            $this->id,
        ];
        if (!$user->has_access(AccessLevelEnum::MANAGER)) {
            $sql .= " AND `user` = ?";
            $params[] = $user->id;
        }

        return Dba::write($sql, $params);
    }

    /**
     * is_valid
     * @param string $secret
     * @param string $action
     */
    public function is_valid($secret, $action): bool
    {
        if ($this->isNew()) {
            debug_event(self::class, 'Access Denied: Invalid share.', 3);

            return false;
        }

        if (!AmpConfig::get('share')) {
            debug_event(self::class, 'Access Denied: share feature disabled.', 3);

            return false;
        }

        if ($this->expire_days > 0 && ($this->creation_date + ($this->expire_days * 86400)) < time()) {
            debug_event(self::class, 'Access Denied: share expired.', 3);

            return false;
        }

        if ($this->max_counter > 0 && $this->counter >= $this->max_counter) {
            debug_event(self::class, 'Access Denied: max counter reached.', 3);

            return false;
        }

        if (
            $this->secret !== null &&
            $this->secret !== '' &&
            $this->secret !== '0' &&
            $secret != $this->secret
        ) {
            debug_event(self::class, 'Access Denied: secret requires to access share ' . $this->id . '.', 3);

            return false;
        }

        if ($action == 'download' && (!AmpConfig::get('download') || !$this->allow_download)) {
            debug_event(self::class, 'Access Denied: download unauthorized.', 3);

            return false;
        }

        if ($action == 'stream' && !$this->allow_stream) {
            debug_event(self::class, 'Access Denied: stream unauthorized.', 3);

            return false;
        }

        return true;
    }

    /**
     * Has this media object come from a shared object?
     */
    public function is_shared_media(int $media_id): bool
    {
        $objectType = $this->getObjectType();

        switch ($objectType) {
            case LibraryItemEnum::ALBUM:
            case LibraryItemEnum::ALBUM_DISK:
            case LibraryItemEnum::PLAYLIST:
                /** @var Album|AlbumDisk|Playlist $object */
                $object = $this->getLibraryItemLoader()->load(
                    LibraryItemEnum::from((string) $this->object_type),
                    $this->object_id,
                    [Album::class, AlbumDisk::class, Playlist::class]
                );

                return in_array(
                    $media_id,
                    $object->get_songs(),
                    true
                );
            default:
                return ($this->object_type == 'song' || $this->object_type == 'video') && $this->object_id === $media_id;
        }
    }

    public function create_fake_playlist(): Stream_Playlist
    {
        $playlist = new Stream_Playlist(-1);
        $medias   = [];

        $objectType = $this->getObjectType();

        switch ($objectType) {
            case LibraryItemEnum::ALBUM:
            case LibraryItemEnum::ALBUM_DISK:
            case LibraryItemEnum::PLAYLIST:
                /** @var Album|AlbumDisk|Playlist $object */
                $object = $this->getLibraryItemLoader()->load(
                    $objectType,
                    $this->object_id,
                    [Album::class, AlbumDisk::class, Playlist::class]
                );

                $medias = $object->get_medias('song');
                break;
            default:
                $medias[] = [
                    'object_type' => $objectType,
                    'object_id' => $this->object_id,
                ];
                break;
        }

        if (!empty($medias)) {
            $playlist->add($medias, '&share_id=' . $this->id . '&share_secret=' . $this->secret);
        }

        return $playlist;
    }

    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    /**
     * get_expiry
     * get the expiry date in days from a time()
     * @param int $time
     */
    public static function get_expiry($time = null): int
    {
        if (isset($time)) {
            // 0 is a valid expiry too
            $expire_days = ((int)$time > 0)
                ? round(($time - time()) / 86400, 0, PHP_ROUND_HALF_EVEN)
                : 0;
        } else {
            // fall back to config defaults
            $expire_days = AmpConfig::get('share_expire', 7);
        }

        return (int)$expire_days;
    }

    /**
     * @param string $object_type
     * @param int $object_id
     * @param bool $show_text
     */
    public static function display_ui($object_type, $object_id, $show_text = true): string
    {
        $result = sprintf(
            '<a onclick="showShareDialog(event, \'%s\', %d);">%s',
            $object_type,
            $object_id,
            Ui::get_material_symbol('share', T_('Share'))
        );

        if ($show_text) {
            $result .= sprintf('&nbsp;%s', T_('Share'));
        }

        return $result . '</a>';
    }

    public function getObjectType(): LibraryItemEnum
    {
        return LibraryItemEnum::from((string) $this->object_type);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getLibraryItemLoader(): LibraryItemLoaderInterface
    {
        global $dic;

        return $dic->get(LibraryItemLoaderInterface::class);
    }
}
