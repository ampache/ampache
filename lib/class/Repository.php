<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Lib;

use Lib\Interfaces\Model;

/**
 * Description of Repository
 *
 * @author raziel
 */
class Repository
{
    protected $modelClassName;

    /**
     *
     * @var array Stores relation between SQL field name and class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = array();

    protected function findBy($fields, $values)
    {
        $table = $this->getTableName();

        return $this->getRecords($table, $fields, $values);
    }

    /**
     *
     * @return DatabaseObject[]
     */
    public function findAll()
    {
        $table = $this->getTableName();

        return $this->getRecords($table);
    }

    /**
     *
     * @param type $object_id
     * @return DatabaseObject
     */
    public function findById($object_id)
    {
        $rows = $this->findBy(array('id'), array($object_id));

        return count($rows) ? reset($rows) : null;
    }

    /**
     * @param string $table
     */
    private function getRecords($table, $field = null, $value = null)
    {
        $data = array();
        $sql  = $this->assembleQuery($table, $field);

        $statement = \Dba::read($sql, is_array($value) ? $value : array($value));
        while ($object = \Dba::fetch_object($statement, $this->modelClassName)) {
            $data[$object->getId()] = $object;
        }

        return $data;
    }

    /**
     *
     * @param string $name
     * @param array $arguments
     * @return DatabaseObject
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^findBy(.*)$/', $name, $matches)) {
            $parts = explode('And', $matches[1]);

            return $this->findBy(
                    $parts,
                    $this->resolveObjects($arguments)
            );
        }
    }

    private function getTableName()
    {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $tableName = preg_replace_callback(
                '/(?<=.)([A-Z])/',
                function ($name) {
                    return '_' . strtolower((string) $name[0]);
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
            $this->updateRecord($object->getId(), $properties);
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

    protected function updateRecord($id, $properties)
    {
        $sql = 'UPDATE ' . $this->getTableName()
                . ' SET ' . implode(',', $this->getKeyValuePairs($properties))
                . ' WHERE id = ?';
        $properties[] = $id;
        \Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );
    }

    protected function deleteRecord($object_id)
    {
        $sql = 'DELETE FROM ' . $this->getTableName()
                . ' WHERE id = ?';
        \Dba::write($sql, array($object_id));
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
     * @param Model $object
     * @param string $property
     * @param string|null $value
     */
    protected function setPrivateProperty(Model $object, $property, $value)
    {
        $reflectionClass    = new \ReflectionClass(get_class($object));
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
        foreach ($properties as $property => $value) {
            if (is_object($value)) {
                $properties[$property] = $value->getId();
            }
        }

        return $properties;
    }

    /**
     * Create query for one or multiple fields
     * @param string $table
     * @param array $fields
     * @return string
     */
    public function assembleQuery($table, $fields)
    {
        $sql = 'SELECT * FROM ' . $table;
        if (!empty($fields)) {
            $sql .= ' WHERE ';
            $sqlParts = array();
            foreach ($fields as $field) {
                $sqlParts[] = '`' . $this->camelCaseToUnderscore($field) . '` = ?';
            }
            $sql .= implode(' and ', $sqlParts);
        }

        return $sql;
    }

    public function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $string));
    }
} // end repository
