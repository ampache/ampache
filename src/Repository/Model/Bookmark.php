<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Repository\BookmarkRepositoryInterface;

/**
 * This manage bookmark on playable items
 */
final class Bookmark extends database_object implements BookmarkInterface
{
    private BookmarkRepositoryInterface $bookmarkRepository;

    private ModelFactoryInterface $modelFactory;

    private int $object_id;

    private ?string $object_type;

    private ?int $user_id;

    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        ModelFactoryInterface $modelFactory,
        int $objectId,
        ?string $objectType = null,
        ?int $userId = null
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->modelFactory       = $modelFactory;
        $this->object_id          = $objectId;
        $this->object_type        = $objectType;
        $this->user_id            = $userId;
    }

    public function getId(): int
    {
        return $this->object_id;
    }

    public function getUserId(): int
    {
        return (int) ($this->getDbData()['user'] ?? 0);
    }

    public function getObjectId(): int
    {
        return (int) ($this->getDbData()['object_id'] ?? 0);
    }

    public function getUpdateDate(): int
    {
        return (int) ($this->getDbData()['update_date'] ?? 0);
    }

    public function getCreationDate(): int
    {
        return (int) ($this->getDbData()['creation_date'] ?? 0);
    }

    public function getComment(): string
    {
        return $this->getDbData()['comment'] ?? '';
    }

    public function getPosition(): int
    {
        return (int) ($this->getDbData()['position'] ?? 0);
    }

    public function getObjectType(): string
    {
        return $this->getDbData()['object_type'] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            if ($this->object_type === null) {
                $this->dbData = $this->bookmarkRepository->getDataById($this->object_id);
            } else {
                $bookmarkIds = $this->bookmarkRepository->lookup(
                    $this->object_type,
                    $this->object_id,
                    $this->user_id
                );

                if ($bookmarkIds === []) {
                    $this->dbData = [];
                } else {
                    $this->dbData = $this->bookmarkRepository->getDataById(current($bookmarkIds));
                }
            }
        }

        return $this->dbData;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getUserName(): string
    {
        return $this->modelFactory->createUser($this->getUserId())->username;
    }
}
