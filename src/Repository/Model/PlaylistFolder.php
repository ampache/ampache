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

use Ampache\Module\Database\database_object;
use Ampache\Repository\PlaylistFolderRepositoryInterface;

/**
 * A node in a user's private tree for organising playlists, smartlists and collections
 *
 * The tree belongs to one user and is never shared. Placement of a list is recorded per (user, list), so
 * filing another user's public playlist changes nothing for them. A folder holds no playable media itself and
 * is never rated, flagged, tagged or given art, which is why it is not a `library_item`.
 */
final class PlaylistFolder extends database_object implements ModelInterface
{
    /**
     * Separator used when a folder is addressed by name path rather than by id.
     */
    public const string PATH_SEPARATOR = '/';
    /**
     * The root of every user's tree; it has no row of its own.
     */
    public const int ROOT = 0;

    /**
     * Object types a folder may hold, in the spelling the tables use.
     *
     * @var list<string>
     */
    public const array VALID_TYPES = ['playlist', 'search', 'collection'];

    protected const string DB_TABLENAME = 'playlist_folder';

    public int $date        = 0;
    public int $id          = 0;
    public int $last_update = 0;
    public string $name     = '';
    public int $parent      = self::ROOT;
    public int $sort_order  = 0;
    public int $user        = 0;

    public function __construct(?int $folderId = 0)
    {
        if (!$folderId) {
            return;
        }

        $info = $this->get_info($folderId, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->assign($info);
        $this->id = (int) $folderId;
    }

    /**
     * The spelling the API uses for a stored type; a `search` is a smartlist everywhere outside the tables.
     */
    public static function denormalizeType(string $objectType): string
    {
        return ($objectType === 'search') ? 'smartlist' : $objectType;
    }

    /**
     * Build a folder from a row that has already been read, so a listing costs one query rather than one each
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $folder = new self();
        $folder->assign($row);
        $folder->id = (int) ($row['id'] ?? 0);

        return $folder;
    }

    /**
     * Whether a name can be stored and addressed; the separator would make its path ambiguous.
     */
    public static function isValidName(string $name): bool
    {
        $name = trim($name);

        return $name !== '' && !str_contains($name, self::PATH_SEPARATOR) && mb_strlen($name) <= 255;
    }

    public static function isValidType(string $objectType): bool
    {
        return in_array($objectType, self::VALID_TYPES, true);
    }

    /**
     * Normalise the API spelling of a type onto the one used for loading and storage.
     */
    public static function normalizeType(string $objectType): string
    {
        return ($objectType === 'smartlist') ? 'search' : $objectType;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int
    {
        return $this->parent;
    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function getUserId(): int
    {
        return $this->user;
    }

    public function isNew(): bool
    {
        return $this->id === 0;
    }

    /**
     * Whether this folder is the owner's to read and write; another user's tree reports as absent, not denied.
     */
    public function isVisible(?User $user): bool
    {
        return $user !== null && !$this->isNew() && $this->user === $user->getId();
    }

    public function save(): void
    {
        $repository = $this->getPlaylistFolderRepository();

        if ($this->isNew()) {
            $this->id = (int) $repository->persist($this);

            return;
        }

        $repository->update($this->id, $this->name, $this->parent, $this->sort_order);
    }

    public function setName(string $name): self
    {
        if (self::isValidName($name)) {
            $this->name = trim($name);
        }

        return $this;
    }

    public function setParentId(int $parentId): self
    {
        $this->parent = max(self::ROOT, $parentId);

        return $this;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sort_order = $sortOrder;

        return $this;
    }

    public function setUserId(int $userId): self
    {
        $this->user = $userId;

        return $this;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function assign(array $row): void
    {
        $this->user        = (int) ($row['user'] ?? 0);
        $this->parent      = (int) ($row['parent'] ?? self::ROOT);
        $this->name        = (string) ($row['name'] ?? '');
        $this->sort_order  = (int) ($row['sort_order'] ?? 0);
        $this->date        = (int) ($row['date'] ?? 0);
        $this->last_update = (int) ($row['last_update'] ?? 0);
    }

    /**
     * @deprecated inject dependency
     */
    private function getPlaylistFolderRepository(): PlaylistFolderRepositoryInterface
    {
        global $dic;

        return $dic->get(PlaylistFolderRepositoryInterface::class);
    }
}
