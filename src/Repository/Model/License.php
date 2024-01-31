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

use Ampache\Module\System\Dba;

class License
{
    protected const DB_TABLENAME = 'license';

    private int $id                = 0;
    private ?string $name          = null;
    private ?string $description   = null;
    private ?string $external_link = null;

    /**
     * Constructor
     * This pulls the license information from the database and returns
     * a constructed object
     * @param int|null $license_id
     */
    public function __construct($license_id = 0)
    {
        if (!$license_id) {
            return;
        }
        $this->has_info($license_id);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * has_info
     * does the db call, reads from the license table
     * @param int $license_id
     */
    private function has_info($license_id): bool
    {
        $sql        = "SELECT * FROM `license` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($license_id));
        $data       = Dba::fetch_assoc($db_results);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    public function getLinkFormatted(): string
    {
        if ($this->external_link !== '') {
            return sprintf(
                '<a href="%s">%s</a>',
                $this->external_link,
                $this->name
            );
        }

        return $this->getName();
    }
}
