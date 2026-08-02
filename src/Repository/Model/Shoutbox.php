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

use Ampache\Module\Database\BaseModel;
use Ampache\Repository\ShoutRepository;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use DateTime;
use DateTimeInterface;

/**
 * Shoutbox item
 *
 * @see ShoutRepository
 */
class Shoutbox extends BaseModel
{
    /** @var string|null Offset position in songs */
    private ?string $data = null;

    /** @var int Date */
    private int $date = 0;

    /** @var int Linked object-id */
    private int $object_id = 0;

    /** @var string|null Linked object-type */
    private ?string $object_type = null;

    /** @var bool True if a sticky shout */
    private bool $sticky = false;

    /** @var string Comment */
    private string $text = '';

    /** @var int User-id */
    private int $user = 0;

    private ?User $user_object = null;

    public function __construct(
        private readonly ShoutRepositoryInterface $shoutRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Returns the creation-date
     */
    public function getDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->date);
    }

    /**
     * Returns the related object-id
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    /**
     * Returns the related object-type
     */
    public function getObjectType(): LibraryItemEnum
    {
        return LibraryItemEnum::from((string) $this->object_type);
    }

    /**
     * Returns the related position within a song
     *
     * @return int Position value in seconds
     */
    public function getOffset(): int
    {
        return (int) $this->data;
    }

    /**
     * Returns the shout text
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Returns the shout-creator user
     */
    public function getUser(): ?User
    {
        if ($this->user_object === null) {
            $this->user_object = $this->userRepository->findById($this->user);
        }

        return $this->user_object;
    }

    /**
     * Returns the user-id of the shout-creator
     */
    public function getUserId(): int
    {
        return $this->user;
    }

    /**
     * Returns `true` if the shout is important (`sticky`)
     */
    public function isSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * Persists the object
     */
    public function save(): void
    {
        $result = $this->shoutRepository->persist($this);

        if (
            $result !== null
            && $this->isNew()
        ) {
            $this->id = $result;
        }
    }

    /**
     * Sets the creation-date
     */
    public function setDate(DateTimeInterface $date): Shoutbox
    {
        $this->date = $date->getTimestamp();

        return $this;
    }

    /**
     * Sets the related object-id
     */
    public function setObjectId(int $object_id): Shoutbox
    {
        $this->object_id = $object_id;

        return $this;
    }

    /**
     * Sets the related object-type
     */
    public function setObjectType(LibraryItemEnum $object_type): Shoutbox
    {
        $this->object_type = $object_type->value;

        return $this;
    }

    /**
     * Relates the shout to a certain position in a song
     *
     * @param int $offset Position value in seconds
     */
    public function setOffset(int $offset): Shoutbox
    {
        $this->data = (string) $offset;

        return $this;
    }

    /**
     * Set the importance of the shout
     */
    public function setSticky(bool $sticky): Shoutbox
    {
        $this->sticky = $sticky;

        return $this;
    }

    /**
     * Sets the shout text
     */
    public function setText(string $text): Shoutbox
    {
        $this->text = strip_tags(htmlspecialchars($text));

        return $this;
    }

    /**
     * Sets the shout-creator user
     */
    public function setUser(User $user): Shoutbox
    {
        $this->user        = $user->getId();
        $this->user_object = $user;

        return $this;
    }
}
