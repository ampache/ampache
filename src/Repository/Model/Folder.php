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
use Ampache\Module\WebDav\WebDavDirectoryInterface;
use Ampache\Repository\FolderRepositoryInterface;

/**
 * This is the class responsible for handling the Folder object
 * it is related to the folder table in the database.
 */
class Folder extends database_object implements
    library_item,
    container_item,
    CatalogItemInterface,
    WebDavDirectoryInterface
{
    protected const string DB_TABLENAME = 'folder';

    public ?int $addition_time = null;
    public int $catalog        = 0;

    /** @var array<int, array{object_type: LibraryItemEnum, object_id: int}>|null $children */
    public ?array $children = null;

    public int $id              = 0;
    public ?string $link        = null;
    public ?string $name        = null;
    public ?int $object_count   = null;
    public ?int $parent         = null;
    public ?string $parent_link = null;
    public ?string $path        = null;
    public ?string $path_name   = null;
    public bool $playable       = false;

    /** @var int[] $podcast_episodes */
    public array $podcast_episodes = [];

    /** @var int[] $songs */
    public array $songs = [];

    public int $total_count  = 0;
    public int $total_skip   = 0;
    public ?int $update_time = null;
    public ?int $user        = null;

    /** @var int[] $videos */
    public array $videos = [];

    public int $weight             = 0;
    private ?string $f_link        = null;
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
                'name' => T_('Home'),
                'path_name' => DIRECTORY_SEPARATOR
            ];
        } else {
            $info = $this->get_info($folder_id, static::DB_TABLENAME);
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * create
     * @param array{
     *     name: string,
     *     catalog: int,
     *     path_name: string,
     *     parent: int|null
     * } $data
     */
    public static function create(array $data): ?int
    {
        $name          = $data['name'];
        $catalog       = $data['catalog'];
        $path_name     = $data['path_name'];
        $parent        = $data['parent'];
        $path          = '';
        $user          = null;
        $addition_time = time();
        $update_time   = filemtime($data['path_name']);

        // Build the folder paths
        if ($parent) {
            // identify full path when missing based on history
            $parentFolder = self::getFolderRepository()->findById((int) $parent);
            while ($parentFolder) {
                $path_name    = $path_name ?: $parentFolder->get_fullpathname() . DIRECTORY_SEPARATOR . $path_name;
                $path         = $parentFolder->id . ($path ? ',' : '') . $path;
                $parentFolder = ($parentFolder->parent)
                    ? self::getFolderRepository()->findById($parentFolder->parent)
                    : null;
            }
        }

        if (!$parent && $path_name) {
            $parent = self::getFolderRepository()->lookup(str_replace(DIRECTORY_SEPARATOR . $name, '', $path_name), $catalog) ?: null;
        }

        $sql = "INSERT INTO `folder` (`name`, `catalog`, `parent`, `user`, `addition_time`, `update_time`, `path`, `path_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$name, $catalog, $parent, $user, $addition_time, $update_time, $path, $path_name]);

        $folder_id = Dba::insert_id();
        if (!$folder_id) {
            return null;
        }

        return (int) $folder_id;
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
     * get_name_by_id
     */
    public static function get_name_by_id(?int $folder_id = 0): string
    {
        if (empty($folder_id)) {
            return '';
        }

        if (database_object::is_cached('folder_name_by_id', $folder_id)) {
            return database_object::get_from_cache('folder_name_by_id', $folder_id)[0];
        }

        $sql        = "SELECT `folder`.`name` AS `f_name` FROM `folder` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$folder_id]);
        if ($row = Dba::fetch_assoc($db_results)) {
            database_object::add_to_cache('folder_name_by_id', $folder_id, [$row['f_name']]);

            return $row['f_name'];
        }

        return '';
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

    /**
     * @deprecated inject dependency
     */
    private static function getFolderRepository(): FolderRepositoryInterface
    {
        global $dic;

        return $dic->get(FolderRepositoryInterface::class);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('folder', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * Search for direct children of an object
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_children(string $name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);
        if ($this->getId() === -1) {
            $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` IS NULL ORDER BY `name`;";
            $db_results = Dba::read($sql);
        } else {
            $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ? ORDER BY `name`;";
            $db_results = Dba::read($sql, [$this->id]);
        }
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $object_type = LibraryItemEnum::tryFrom($row['object_type']);
            if ($object_type !== null) {
                $results[] = [
                    'object_type' => $object_type,
                    'object_id' => (int) $row['object_id']
                ];
            }
        }

        return $results;
    }

    /**
     * @see WebDavDirectory::getChildren
     * @return array{string?: array<int, array{object_type: LibraryItemEnum, object_id: int}>}
     */
    public function get_childrens(): array
    {
        $results = [];
        foreach ($this->get_children((string) $this->get_fullpathname()) as $objects) {
            $results[] = [
                'object_type' => $objects['object_type'],
                'object_id' => $objects['object_id']
            ];
        }

        return ['podcast_episode' => $results];
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
     * Get root path link.
     */
    public function get_f_home_link(): string
    {
        $t_home   = T_('Home');
        $web_path = AmpConfig::get_web_path();

        return "<a href=\"" . $web_path . "/folders.php?action=show&folder=-1\" title=\"" . $t_home . "\">" . $t_home . "</a>";
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($title ?? $this->get_fullname());
        }

        return $this->f_link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_parent_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_parent_link === null && $this->parent) {
            $parent_name         = scrub_out(self::get_fullname_by_id($this->parent));
            $this->f_parent_link = "<a href=\"" . $this->get_parent_link() . "\" title=\"" . $parent_name . "\">" . $parent_name . "</a>";
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
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * get_fullname_by_id
     */
    public function get_fullname_by_id(?int $folder_id = 0): string
    {
        if (empty($folder_id)) {
            return '';
        }

        if (database_object::is_cached('folder_fullname_by_id', $folder_id)) {
            return database_object::get_from_cache('folder_fullname_by_id', $folder_id)[0];
        }

        $sql        = "SELECT `folder`.`name` AS `f_name` FROM `folder` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$folder_id]);
        if ($row = Dba::fetch_assoc($db_results)) {
            database_object::add_to_cache('folder_fullname_by_id', $folder_id, [$row['f_name']]);

            return $row['f_name'];
        }

        return '';
    }

    /**
     * get_fullpathname
     */
    public function get_fullpathname(): ?string
    {
        return $this->path_name;
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
                'value' => (string) $this->get_fullname()
            ],
        ];
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
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        if ($filter_type === null) {
            $sql    = "SELECT `folder_map`.`object_id`, `folder_map`.`object_type` FROM `folder_map` WHERE `folder_map`.`object_type` != 'folder' AND (`folder_map`.`folder_id` = ? OR `folder_map`.`path_name` LIKE ?) ORDER BY `folder_map`.`name`;";
            $params = [$this->id, $this->path_name . '/%'];
        } else {
            $sql    = "SELECT `folder_map`.`object_id`, `folder_map`.`object_type` FROM `folder_map` WHERE `folder_map`.`object_type` = ? AND (`folder_map`.`folder_id` = ? OR `folder_map`.`path_name` LIKE ?) ORDER BY `folder_map`.`name`;";
            $params = [$filter_type, $this->id, $this->path_name . '/%'];
        }
        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $object_type = LibraryItemEnum::tryFrom($row['object_type']);
            if ($object_type !== null) {
                $results[] = [
                    'object_type' => $object_type,
                    'object_id' => (int) $row['object_id']
                ];
            }
        }

        return $results;
    }

    /**
     * get_objects
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int
     * }>
     */
    public function get_objects(): array
    {
        if (empty($this->children)) {
            if ($this->getId() === -1) {
                $sql        = "SELECT `id` AS `object_id`, 'folder' AS `object_type` FROM `folder` WHERE `parent` IS NULL;";
                $db_results = Dba::read($sql);
            } else {
                $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;";
                $db_results = Dba::read($sql, [$this->getId()]);
            }

            $results    = [];
            while ($row = Dba::fetch_assoc($db_results)) {
                $object_type = LibraryItemEnum::tryFrom($row['object_type']);
                if ($object_type !== null) {
                    $results[] = [
                        'object_type' => $object_type,
                        'object_id' => (int) $row['object_id']
                    ];
                }
            }

            $this->children = $results;
        }

        return $this->children;
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

    public function get_parent_fullname(): string
    {
        return self::get_fullname_by_id($this->parent);
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
     * get_user_owner
     */
    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::FOLDER;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'folder');
    }

    public function has_children(string $name): bool
    {
        debug_event(self::class, 'has_children ' . $name, 5);
        $sql        = "SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;";
        $db_results = Dba::read($sql, [$this->id]);

        return (Dba::num_rows($db_results) > 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * update
     */
    public function update(array $data): ?int
    {
        $name         = $data['name'] ?? $this->name;
        $catalog      = $data['catalog'] ?? $this->catalog;
        $parent       = $data['parent'] ?? $this->parent;
        $update_time  = filemtime($data['path_name'] ?? $this->path_name);

        $sql = "UPDATE `folder` SET `name` = ?, `catalog` = ?, `parent` = ?, `update_time` = ? WHERE `id` = ?";
        Dba::write($sql, [$name, $catalog, $parent, $update_time, $this->id]);

        return $this->id;
    }
}
