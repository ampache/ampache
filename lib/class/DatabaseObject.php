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

namespace Lib;

/**
 * Description of Model
 *
 * @author raziel
 */
abstract class DatabaseObject
{
    protected $id;
    //private $originalData;

    /**
     *
     * @var array Stores relation between SQL field name and class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = array();

    public function __construct()
    {
        $this->remapCamelcase();
        $this->initializeChildObjects();
        //$this->originalData = get_object_vars($this);
    }

    public function getId()
    {
        return $this->id;
    }

    protected function isPropertyDirty($property)
    {
        return $this->originalData->$property !== $this->$property;
    }

    public function isDirty()
    {
        return true;
    }

    /**
     * Get all changed properties
     * TODO: we get all properties for now...need more logic here...
     * @return array
     */
    public function getDirtyProperties()
    {
        $properties = get_object_vars($this);
        unset($properties['id']);
        unset($properties['fieldClassRelations']);
        return $this->fromCamelCase($properties);
    }

    /**
     * Convert the object properties to camelCase.
     * This works in constructor because the properties are here from
     * fetch_object before the constructor get called.
     */
    protected function remapCamelcase()
    {
        foreach (get_object_vars($this) as $key => $val) {
            if (strpos($key, '_') !== false) {
                $camelCaseKey        = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $this->$camelCaseKey = $val;
                unset($this->$key);
            }
        }
    }
    
    protected function fromCamelCase($properties)
    {
        $data = array();
        foreach ($properties as $propertie => $value) {
            $newPropertyKey        = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $propertie));
            $data[$newPropertyKey] = $value;
        }
        return $data;
    }

    /**
     * Adds child Objects based of the Model Information
     * TODO: Someday we might need lazy loading, but for now it should be ok.
     */
    public function initializeChildObjects()
    {
        foreach ($this->fieldClassRelations as $field => $repositoryName) {
            if (class_exists($repositoryName)) {
                /* @var $repository Repository */
                $repository   = new $repositoryName;
                $this->$field = $repository->findById($this->$field);
            }
        }
    }
}
