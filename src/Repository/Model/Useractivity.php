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

declare(strict_types=1);

namespace Ampache\Repository\Model;

final class Useractivity extends database_object implements UseractivityInterface
{
    protected const DB_TABLENAME = 'user_activity';

    private int $id;

    /**
     * Once loaded from db, holds the internal state
     *
     * @var null|array<string, mixed>
     */
    private ?array $data = null;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    private function getData(): array
    {
        if ($this->data === null) {
            /* Get the information from the db */
            $this->data = $this->get_info($this->id, 'user_activity');
        }

        return $this->data;
    }

    public function getId(): int
    {
        return (int) ($this->getData()['id'] ?? 0);
    }

    public function getUser(): int
    {
        return (int) ($this->getData()['user'] ?? 0);
    }

    public function getObjectType(): string
    {
        return (string) ($this->getData()['object_type'] ?? '');
    }

    public function getObjectId(): int
    {
        return (int) ($this->getData()['object_id'] ?? 0);
    }

    public function getAction(): string
    {
        return (string) ($this->getData()['action'] ?? '');
    }

    public function getActivityDate(): int
    {
        return (int) ($this->getData()['activity_date'] ?? 0);
    }
}
