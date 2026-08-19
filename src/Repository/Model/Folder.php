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
use Ampache\Module\Art\Art;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Module\WebDav\WebDavDirectoryInterface;
use Ampache\Repository\FolderRepositoryInterface;

/**
 * This is the class responsible for handling the Folder object
 * it is related to the folder table in the database.
 */
class Folder extends database_object implements
    library_item,
    container_item,
    displayable_item,
    CatalogItemInterface,
    WebDavDirectoryInterface,
    ModelInterface
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
        $this->addition_time = isset($info['addition_time']) ? (int) $info['addition_time'] : null;
        $this->catalog       = (int) ($info['catalog'] ?? 0);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->link          = $info['link'] ?? null;
        $this->name          = $info['name'] ?? null;
        $this->object_count  = isset($info['object_count']) ? (int) $info['object_count'] : null;
        $this->parent        = isset($info['parent']) ? (int) $info['parent'] : null;
        $this->parent_link   = $info['parent_link'] ?? null;
        $this->path          = $info['path'] ?? null;
        $this->path_name     = $info['path_name'] ?? null;
        $this->playable      = (bool) ($info['playable'] ?? false);
        $this->total_count   = (int) ($info['total_count'] ?? 0);
        $this->total_skip    = (int) ($info['total_skip'] ?? 0);
        $this->update_time   = isset($info['update_time']) ? (int) $info['update_time'] : null;
        $this->user          = isset($info['user']) ? (int) $info['user'] : null;
    }

    /**
     * build_cache
     * This attempts to reduce # of queries by asking for everything in the
     * browse all at once and storing it in the cache
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getFolderRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('folder', (int) $row['id'], $row);
        }

        return true;
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
        $update_time   = (is_dir($data['path_name']) && filemtime($data['path_name'])) ?: time();

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

        $folder                = new Folder();
        $folder->name          = $name;
        $folder->catalog       = $catalog;
        $folder->parent        = $parent;
        $folder->user          = $user;
        $folder->addition_time = $addition_time;
        $folder->update_time   = $update_time;
        $folder->path          = $path;
        $folder->path_name     = $path_name;

        return self::getFolderRepository()->persist($folder);
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

        $name = self::getFolderRepository()->getNameById($folder_id);
        if ($name !== null) {
            database_object::add_to_cache('folder_name_by_id', $folder_id, [$name]);

            return $name;
        }

        return '';
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getFolderRepository()->migrateObject($object_type, $old_object_id, $new_object_id);
    }

    /**
     * The current request's user id, or -1 (system/guest) outside a user context
     */
    private static function currentUserId(): int
    {
        return Core::get_global('user')?->getId() ?? -1;
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

        return self::getFolderRepository()->getChildren(
            ($this->getId() === -1) ? null : $this->id,
            self::currentUserId()
        );
    }

    /**
     * @see WebDavDirectory::getChildren
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
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

        return $results;
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
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($title ?? $this->get_fullname()) . "</a>";
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

        $name = self::getFolderRepository()->getNameById($folder_id);
        if ($name !== null) {
            database_object::add_to_cache('folder_fullname_by_id', $folder_id, [$name]);

            return $name;
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
     * How many playable items sit below this folder, subfolders included
     */
    public function get_media_count(): int
    {
        return self::getFolderRepository()->getMediaCount($this, self::currentUserId());
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        return self::getFolderRepository()->getMedias($this, $filter_type, self::currentUserId());
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
            $this->children = self::getFolderRepository()->getObjects(
                ($this->getId() === -1) ? null : $this->getId(),
                self::currentUserId()
            );
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

    public function getCatalog(): int
    {
        return $this->catalog;
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

    /**
     * The id of the folder this one hangs off.
     */
    public function getParentId(): ?int
    {
        if ($this->id === -1) {
            return null;
        }

        return $this->parent ?? -1;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'folder');
    }

    public function has_children(string $name): bool
    {
        debug_event(self::class, 'has_children ' . $name, 5);

        return self::getFolderRepository()->hasChildren($this->id, self::currentUserId());
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Persists the object
     *
     * An object that has not been saved yet will receive the id it was given
     */
    public function save(): void
    {
        $result = self::getFolderRepository()->persist($this);

        if ($result !== null) {
            $this->id = $result;
        }

        // memory_cache is on by default, so the row this object just wrote has to leave the request cache
        self::remove_from_cache('folder', $this->id);
    }

    /**
     * update
     */
    public function update(array $data): ?int
    {
        $this->name    = $data['name'] ?? $this->name;
        $this->catalog = (int) ($data['catalog'] ?? $this->catalog);
        $this->parent  = isset($data['parent'])
            ? (int) $data['parent']
            : $this->parent;
        $this->update_time = filemtime($data['path_name'] ?? $this->path_name) ?: null;

        self::getFolderRepository()->persist($this);

        return $this->id;
    }
}
