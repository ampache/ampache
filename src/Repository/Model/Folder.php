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
use Ampache\Module\System\Dba;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * This is the class responsible for handling the Folder object
 * it is related to the folder table in the database.
 */
class Folder extends database_object implements
    library_item,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'folder';

    public int $id = 0;

    public ?string $name = null;

    public int $catalog = 0;

    public int $parent = 0;

    public ?int $user = null;

    public ?int $update_time = null;

    public ?int $addition_time = null;

    private ?int $object_count = null;

    public int $total_count = 0;

    public int $total_skip = 0;

    public ?string $path = null;

    public ?string $path_name = null;

    public ?string $link = null;

    public ?string $parent_link = null;

    /** @var array<int, array{object_type: LibraryItemEnum|null, object_id: int}>|null $children */
    public ?array $children = null;

    /** @var int[] $artists */
    public array $artists = [];

    /** @var int[] $albums */
    public array $albums = [];

    /** @var int[] $podcasts */
    public array $podcasts = [];

    /** @var int[] $podcast_episodes */
    public array $podcast_episodes = [];

    /** @var int[] $songs */
    public array $songs = [];

    /** @var int[] $videos */
    public array $videos = [];

    private ?string $f_link = null;

    private ?string $f_parent_link = null;

    /**
     * __construct
     */
    public function __construct(?int $folder_id = 0)
    {
        if (!$folder_id) {
            return;
        }

        if ($folder_id === -1) {
            $info = [
                'id' => -1,
                'name' => T_('root'),
                'path_name' => DIRECTORY_SEPARATOR
            ];
        } else {
            $info = $this->get_info($folder_id, static::DB_TABLENAME);
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('folder', $this->id, (string)$this->get_fullname(), $size, $this->get_link());
        }
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'folder');
    }

    /**
     * @see WebDavDirectory::getChildren
     * @return array{string?: array<int, array{object_type: LibraryItemEnum, object_id: int}>}
     */
    public function get_childrens(): array
    {
        return [];
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
        return $this->summary ?? '';
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * get_fullpathname
     */
    public function get_fullpathname(): ?string
    {
        return $this->path_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . '/folders.php?action=show&folder=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($this->get_fullname());
        }

        return $this->f_link;
    }

    /**
     * Get item link.
     */
    public function get_parent_link(): string
    {
        // don't do anything if it's formatted
        if ($this->parent_link === null && $this->parent) {
            $web_path = AmpConfig::get_web_path();

            $this->parent_link = $web_path . '/folders.php?action=show&folder=' . $this->parent;
        }

        return $this->parent_link ?? '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_parent_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_parent_link === null && $this->parent) {
            $this->f_parent_link = "<a href=\"" . $this->get_parent_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($this->get_fullname());
        }

        return $this->f_parent_link ?? '';
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [
            'folder' => [
                'important' => true,
                'label' => T_('Folder'),
                'value' => (string)$this->get_fullname()
            ],
        ];
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'song') {
            $songs = $this->getSongRepository()->getByFolder((string)$this->name);
            foreach ($songs as $song_id) {
                $medias[] = ['object_type' => LibraryItemEnum::SONG, 'object_id' => $song_id];
            }
        }

        return $medias;
    }

    /**
     * get_parent
     * @return null|array{object_type: LibraryItemEnum, object_id: int}
     */
    public function get_parent(): ?array
    {
        $parent = self::getFolderRepository()->findById($this->parent);
        if (!$parent) {
            return null;
        }

        return [
            'object_type' => LibraryItemEnum::FOLDER,
            'object_id' => $parent->getId()
        ];
    }

    /**
     * get_user_owner
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    /**
     * Search for direct children of an object
     * @return array<int, array{object_type: LibraryItemEnum|null, object_id: int}>
     */
    public function get_children(string $name): array
    {
        $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;";
        $db_results = Dba::read($sql, [$this->id]);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'object_type' => LibraryItemEnum::tryFrom($row['object_type']),
                'object_id' => (int)$row['object_id']
            ];
        }

        return $results;
    }

    /**
     * update
     */
    public function update(array $data): ?int
    {
        // duplicate name check
        if (self::getFolderRepository()->lookup($data['name'], ($data['catalog'] ?? $this->catalog)) !== 0) {
            return null;
        }

        $name         = $data['name'] ?? $this->name;
        $catalog      = $data['catalog'] ?? $this->catalog;
        $parent       = $data['parent'] ?? null;
        $update_time  = time();
        $object_count = $data['object_count'] ?? null;

        $sql = "UPDATE `folder` SET `name` = ?, `catalog` = ?, `parent` = ?, `update_time` = ?, `object_count` = ? WHERE `id` = ?";
        Dba::write($sql, [$name, $catalog, $parent, $update_time, $object_count, $this->id]);

        return $this->id;
    }

    /**
     * create
     * @param array{
     *     name: string,
     *     catalog: int,
     *     parent?: int,
     *     user?: int|null,
     *     addition_time?: int,
     *     path?: string,
     *     path_name?: string
     * } $data
     */
    public static function create(array $data): ?int
    {
        if (self::getFolderRepository()->lookup($data['name'], $data['catalog']) !== 0) {
            return null;
        }

        $name          = $data['name'];
        $catalog       = $data['catalog'];
        $parent        = (isset($data['parent'])) ? $data['parent'] : null;
        $user          = $data['user'] ?? null;
        $addition_time = $data['addition_time'] ?? time();

        // Build the folder paths
        $path      = $data['path'] ?? '';
        $path_name = $data['path_name'] ?? '';
        if ($parent && (!$path || !$path_name)) {
            // identify full path when missing based on history
            $parentFolder = self::getFolderRepository()->findById((int)$parent);
            while ($parentFolder) {
                $path_name    = $parentFolder->get_fullpathname() . DIRECTORY_SEPARATOR . $path_name;
                $path         = $parentFolder->id . ($path ? ',' : '') . $path;
                $parentFolder = ($parentFolder->parent)
                    ? self::getFolderRepository()->findById($parentFolder->parent)
                    : null;
            }
        }

        if (!$parent && $path && $path_name) {
            $parent = self::getFolderRepository()->lookup(str_replace(DIRECTORY_SEPARATOR . $path, '', $path_name), $catalog);
        }

        $sql = "INSERT INTO `folder` (`name`, `catalog`, `parent`, `user`, `addition_time`, `path`, `path_name`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$name, $catalog, $parent, $user, $addition_time, $path, $path_name]);

        $folder_id = Dba::insert_id();
        if (!$folder_id) {
            return null;
        }

        return (int)$folder_id;
    }

    /**
     * get_objects
     * @return array<int, array{
     *     object_type: LibraryItemEnum|null,
     *     object_id: int
     * }>
     */
    public function get_objects(): array
    {
        if (empty($this->children)) {
            $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;";
            $db_results = Dba::read($sql, [$this->id]);
            $results    = [];
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = [
                    'object_type' => LibraryItemEnum::tryFrom($row['object_type']),
                    'object_id' => (int)$row['object_id']
                ];
            }

            $this->children = $results;
        }

        return $this->children;
    }

    /**
     * get_display
     * This returns a csv formatted version of the folders that we are given
     * @param string[] $folders
     */
    public static function get_display(array $folders, bool $link = false): string
    {
        if (empty($folders)) {
            return '';
        }

        $web_path = AmpConfig::get_web_path();

        $results = '';
        // Iterate through the folders, format them according to type and element id
        foreach ($folders as $folder_id => $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/folders.php?action=show&folder=' . $folder_id . '" title="' . $value . '">';
            }

            $results .= $value;
            if ($link) {
                $results .= '</a>';
            }

            $results .= ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        $sql    = "UPDATE `folder_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;";
        $params = [$new_object_id, $old_object_id, $object_type];

        Dba::write($sql, $params);
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::FOLDER;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getFolderRepository(): FolderRepositoryInterface
    {
        global $dic;

        return $dic->get(FolderRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
