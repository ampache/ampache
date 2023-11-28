<?php

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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;

class Shoutbox
{
    protected const DB_TABLENAME = 'user_shout';

    public int $id = 0;
    public int $user;
    public string $text;
    public int $date;
    public bool $sticky;
    public int $object_id;
    public ?string $object_type;
    public ?string $data;

    /**
     * Constructor
     * This pulls the shoutbox information from the database and returns
     * a constructed object, uses user_shout table
     * @param int|null $shout_id
     */
    public function __construct($shout_id = 0)
    {
        if (!$shout_id) {
            return;
        }
        $this->has_info($shout_id);
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * has_info
     * does the db call, reads from the user_shout table
     * @param int $shout_id
     */
    private function has_info($shout_id): bool
    {
        $sql        = "SELECT * FROM `user_shout` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($shout_id));
        $data       = Dba::fetch_assoc($db_results);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    public function getStickyFormatted(): string
    {
        return $this->sticky == '0' ? 'No' : 'Yes';
    }

    public function getTextFormatted(): string
    {
        return preg_replace('/(\r\n|\n|\r)/', '<br />', $this->text) ?? '';
    }

    public function getDateFormatted(): string
    {
        return get_datetime((int)$this->date);
    }

    /**
     * Returns true if the object is new/unknown
     */
    public function isNew(): bool
    {
        return $this->getId() === 0;
    }
}
