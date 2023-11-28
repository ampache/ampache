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

use Ampache\Repository\ShoutRepository;
use Ampache\Repository\ShoutRepositoryInterface;
use DateTime;
use DateTimeInterface;

/**
 * Shoutbox item
 *
 * @see ShoutRepository
 */
class Shoutbox
{
    /** @var int Primary key*/
    private int $id = 0;

    /** @var int User-id */
    private int $user = 0;

    /** @var string Comment */
    private string $text = '';

    /** @var int Date */
    private int $date = 0;

    /** @var bool True if a sticky shout */
    private bool $sticky = false;

    /** @var int Linked object-id */
    private int $object_id = 0;

    /** @var string|null Linked object-type */
    private ?string $object_type = null;

    /** @var string|null Offset position in songs */
    private ?string $data = null;

    private ShoutRepositoryInterface $shoutRepository;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository
    ) {
        $this->shoutRepository = $shoutRepository;
    }

    /**
     * Returns true if the object is new/unknown
     */
    public function isNew(): bool
    {
        return $this->id === 0;
    }

    /**
     * Returns the id of the object
     *
     * Will return `0` if the object is not persisted yet
     */
    public function getId(): int
    {
        return $this->id;
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
     * Returns the related position within a song
     *
     * @return int Position value in seconds
     */
    public function getOffset(): int
    {
        return (int) $this->data;
    }

    /**
     * Sets the related object-type
     */
    public function setObjectType(string $object_type): Shoutbox
    {
        $this->object_type = $object_type;

        return $this;
    }

    /**
     * Returns the related object-type
     */
    public function getObjectType(): string
    {
        return (string) $this->object_type;
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
     * Returns the related object-id
     */
    public function getObjectId(): int
    {
        return $this->object_id;
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
     * Returns `true` if the shout is important (`sticky`)
     */
    public function isSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * Returns the creation-date
     */
    public function getDate(): DateTimeInterface
    {
        return new DateTime('@' . $this->date);
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
     * Returns the shout text
     */
    public function getText(): string
    {
        return $this->text;
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
     * Returns the user-id of the shout-creator
     */
    public function getUserId(): int
    {
        return $this->user;
    }

    /**
     * Sets the shout-creator user
     */
    public function setUser(User $user): Shoutbox
    {
        $this->user = $user->getId();

        return $this;
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
}
