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
use library_item;

/**
 * Description of metadata
 *
 * @author raziel
 */
class Metadata extends DatabaseObject implements Model
{
    /**
     * Database ID
     * @var integer
     */
    protected $id;

    /**
     * A library item like song or video
     * @var integer
     */
    protected $objectId;

    /**
     * Tag Field
     * @var MetadataField
     */
    protected $field;

    /**
     * Tag Data
     * @var string
     */
    protected $data;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @var array Stores relation between SQL field name and repository class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = array(
        'field' => '\Lib\Metadata\Repository\MetadataField'
    );

    /**
     *
     * @return integer
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     *
     * @return MetadataField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * setObjectId
     * @param integer $object_id
     */
    public function setObjectId($object_id)
    {
        $this->objectId = $object_id;
    }

    /**
     *
     * @param MetadataField $field
     */
    public function setField(MetadataField $field)
    {
        $this->field = $field;
    }

    /**
     *
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
