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

namespace lib;

/**
 * Description of Repository
 *
 * @author raziel
 */
class Repository
{
    protected $modelClassName;

    protected function findBy($field, $value)
    {
        $table = $this->getTableName();
        return $this->getRecords($table, $field, $value);
    }

    public function findAll()
    {
        $table = $this->getTableName();
        return $this->getRecords($table);
    }

    public function findById($id)
    {
        $rows = $this->findBy('id', $id);
        return count($rows) ? reset($rows) : null;
    }

    private function getRecords($className, $field = null, $value = null)
    {
        $data = array();
        $sql = 'SELECT * FROM ' . $className;
        if ($value) {
            $sql .= ' WHERE ' . $field . ' = ?';
        }

        $statement = \Dba::read($sql, array($value));
        while ($object = \Dba::fetch_object($statement, $this->modelClassName)) {
            $data[$object->getId()] = $object;
        }
        return $data;
    }

    public function __call($name, $arguments)
    {
        if (preg_match('/^findBy(.*)$/', $name, $matches)) {
            return $this->findBy($matches[1], count($arguments) ? $arguments[0] : null);
        }
    }

    private function getTableName()
    {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $tableName = preg_replace_callback(
                '/(?<=.)([A-Z])/',
                function($m) {
                    return '_' . strtolower($m[0]);
                }, end($nameParts));
        return lcfirst($tableName);
    }

    public function add(DatabaseObject $object)
    {
        $properties = $object->getDirtyProperties();
        $this->setPrivateProperty(
                $object,
                'id',
                $this->insertRecord($properties)
        );
    }

    public function update(DatabaseObject $object)
    {
        if ($object->isDirty()) {
            $properties = $object->getDirtyProperties();
            $this->updateRecord($properties);
        }
    }

    public function remove(DatabaseObject $object)
    {
        $id = $object->getId();
        $this->deleteRecord($id);
    }

    protected function insertRecord($properties)
    {
        $sql = 'INSERT INTO ' . $this->getTableName() . ' (' . implode(',', array_keys($properties)) . ')'
                . ' VALUES(' . implode(',', array_fill(0, count($properties), '?')) . ')';
        //print_r($properties);
        \Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );
        return \Dba::insert_id();
    }

    protected function updateRecord($properties)
    {
        $properties[] = $properties['id'];
        $sql = 'UPDATE ' . $this->getTableName()
                . ' SET ' . implode(',', $this->getKeyValuePairs($properties))
                . ' WHERE id = ?';
        \Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );
    }

    protected function deleteRecord($id)
    {
        $sql = 'DELETE FROM ' . $this->getTableName()
                . ' WHERE id = ?';
        \Dba::write($sql, array($id));
    }

    protected function getKeyValuePairs($properties)
    {
        $pairs = array();
        foreach ($properties as $property => $value) {
            $pairs[] = $property . '= ?';
        }
        return $pairs;
    }

    /**
     * Set a private or protected variable.
     * Only used in case where a property should not publicly writable
     * @param Object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setPrivateProperty(Object $object, $property, $value)
    {
        $reflectionClass = new \ReflectionClass(get_class($object));
        $ReflectionProperty = $reflectionClass->getProperty($property);
        $ReflectionProperty->setAccessible(true);
        $ReflectionProperty->setValue($object, $value);
    }

    /**
     * Resolve all objects into id's
     * @param array $properties
     * @return array
     */
    protected function resolveObjects(array $properties)
    {
        foreach($properties as $property => $value) {
            if(is_object($value)) {
                $properties[$property] = $value->getId();
            }
        }
        return $properties;
    }

}
