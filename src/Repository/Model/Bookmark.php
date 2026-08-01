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
use Ampache\Module\System\Core;
use Ampache\Repository\BookmarkRepositoryInterface;

/**
 * This manage bookmark on playable items
 */
class Bookmark extends database_object
{
    protected const string DB_TABLENAME = 'bookmark';

    public ?string $comment = null;
    public int $creation_date;

    // Public variables
    public int $id = 0;
    public int $object_id;
    public ?string $object_type = null;
    public int $position;
    public int $update_date;
    public int $user;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull for
     */
    public function __construct(
        ?int $object_id = 0,
        ?string $object_type = null,
        ?int $user_id = null,
    ) {
        if (!$object_id) {
            return;
        }

        if ($object_type === null) {
            $info = $this->get_info($object_id, static::DB_TABLENAME);
        } else {
            if ($user_id === null) {
                $user    = Core::get_global('user');
                $user_id = $user->id ?? 0;
            }

            if ($user_id === 0) {
                return;
            }

            $info = self::getBookmarkRepository()->getRowByObject($object_type, $object_id, $user_id);
        }

        $this->comment       = $info['comment'] ?? null;
        $this->creation_date = (int) ($info['creation_date'] ?? 0);
        $this->id            = (int) ($info['id'] ?? 0);
        $this->object_id     = (int) ($info['object_id'] ?? 0);
        $this->object_type   = $info['object_type'] ?? null;
        $this->position      = (int) ($info['position'] ?? 0);
        $this->update_date   = (int) ($info['update_date'] ?? 0);
        $this->user          = (int) ($info['user'] ?? 0);
    }

    /**
     * create
     * @param array{
     *     comment: null|string,
     *     object_type: string,
     *     object_id: int,
     *     position: int
     * } $data
     */
    public static function create(array $data, int $userId, int $updateDate): void
    {
        self::getBookmarkRepository()->create(
            $userId,
            (int) $data['position'],
            (string) scrub_in((string) $data['comment']),
            $data['object_type'],
            (int) $data['object_id'],
            $updateDate,
            (bool) AmpConfig::get('bookmark_latest', false)
        );
    }

    /**
     * edit
     * @param array{
     *     position: int,
     *     comment: ?string
     * } $data
     */
    public static function edit(int $bookmarkId, array $data, int $updateDate): void
    {
        self::getBookmarkRepository()->updateWithComment(
            $bookmarkId,
            (int) $data['position'],
            (string) scrub_in((string) $data['comment']),
            $updateDate
        );
    }

    /**
     * getBookmarks
     * @param array{
     *     object_type: string,
     *     object_id: int,
     *     comment: ?string,
     *     user: int,
     *     position?: int
     * } $data
     * @return int[]
     */
    public static function getBookmarks(array $data): array
    {
        $repository = self::getBookmarkRepository();
        if ($data['object_type'] !== 'bookmark') {
            return $repository->findIdsByObject(
                (int) $data['user'],
                $data['object_type'],
                (int) $data['object_id'],
                (empty($data['comment'])) ? null : (string) scrub_in($data['comment'])
            );
        }

        // bookmarks are per user
        return $repository->findIdsByBookmarkId((int) $data['user'], (int) $data['object_id']);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserName(): string
    {
        return User::get_username($this->user);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function ownedByUser(User $user): bool
    {
        return $user->getId() === $this->user;
    }
}
