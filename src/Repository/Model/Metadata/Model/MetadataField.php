<?php
/*
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
 */

namespace Ampache\Repository\Model\Metadata\Model;

use Ampache\Repository\Model\DatabaseObject;
use Ampache\Repository\Model\Model;

class MetadataField extends DatabaseObject implements Model
{
    /**
     * Database ID
     * @var int
     */
    protected $id;

    /**
     * Tag name
     * @var string
     */
    protected $name;

    /**
     * Is the Tag public?
     * @var bool
     */
    protected $public = true;

    /**
     * Get ID of Database Object
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function getFormattedName()
    {
        return ucwords(str_replace("_", " ", $this->name));
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Set public to false
     */
    public function hide()
    {
        $this->public = false;
    }
}
