<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Lib\Metadata\Model;

use Lib\DatabaseObject;
use Lib\Interfaces\Model;

/**
 * Description of metadata_field
 *
 * @author raziel
 */
class MetadataField extends DatabaseObject implements Model
{
    /**
     * Database ID
     * @var integer
     */
    protected $id;

    /**
     * Tag name
     * @var string
     */
    protected $name;

    /**
     * Is the Tag public?
     * @var boolean
     */
    protected $public = true;

    /**
     *
     * @return integer
     */
    public function getId()
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
     * @return boolean
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
