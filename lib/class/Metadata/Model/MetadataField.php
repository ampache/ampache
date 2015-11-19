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

namespace Lib\Metadata\Model;

/**
 * Description of metadata_field
 *
 * @author raziel
 */
class MetadataField extends \Lib\DatabaseObject implements \Lib\Interfaces\Model
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
