<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace lib\Metadata\Model;

/**
 * Description of metadata
 *
 * @author raziel
 */
class Metadata extends \lib\DatabaseObject implements \lib\Interfaces\Model
{
    /**
     * Database ID
     * @var integer
     */
    protected $id;

    /**
     * A library item like song or video
     * @var \library_item
     */
    protected $objectId;

    /**
     * Tag Field
     * @var Metadata_field
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
        'field' => '\lib\Metadata\Repository\MetadataField'
    );

    /**
     *
     * @return \library_item
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
     *
     * @param \library_item $object
     */
    public function setObjectId(\library_item $object)
    {
        $this->objectId = $object;
    }

    /**
     *
     * @param Metadata_field $field
     */
    public function setField(Metadata_field $field)
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
