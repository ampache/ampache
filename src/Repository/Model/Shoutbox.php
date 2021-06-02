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

use Ampache\Repository\ShoutRepositoryInterface;

final class Shoutbox implements ShoutboxInterface
{
    private ShoutRepositoryInterface $shoutRepository;

    private int $id;

    /**
     * @var null|array{
     *  id: int,
     *  user: int,
     *  text: string,
     *  date: int,
     *  sticky:int,
     *  object_id: int,
     *  object_type: string,
     *  data: string
     * }
     */
    private ?array $dbData = null;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        int $id
    ) {
        $this->shoutRepository = $shoutRepository;
        $this->id              = $id;
    }

    /**
     * @var array{
     *  id: int,
     *  user: int,
     *  text: string,
     *  date: int,
     *  sticky:int,
     *  object_id: int,
     *  object_type: string,
     *  data: string
     * }
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->shoutRepository->getDataById($this->id);
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return (int) ($this->getDbData()['id'] ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getObjectType(): string
    {
        return $this->getDbData()['object_type'] ?? '';
    }

    public function getObjectId(): int
    {
        return (int) ($this->getDbData()['object_id'] ?? 0);
    }

    public function getUserId(): int
    {
        return (int) ($this->getDbData()['user'] ?? 0);
    }

    public function getSticky(): int
    {
        return (int) ($this->getDbData()['sticky'] ?? 0);
    }

    public function getText(): string
    {
        return $this->getDbData()['text'] ?? '';
    }

    public function getData(): string
    {
        return $this->getDbData()['data'] ?? '';
    }

    public function getDate(): int
    {
        return (int) ($this->getDbData()['date'] ?? 0);
    }

    public function getStickyFormatted(): string
    {
        return $this->getSticky() == 0 ? 'No' : 'Yes';
    }

    public function getTextFormatted(): string
    {
        return preg_replace('/(\r\n|\n|\r)/', '<br />', $this->getText());
    }

    public function getDateFormatted(): string
    {
        return get_datetime($this->getDate());
    }
}
