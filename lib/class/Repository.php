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

namespace Lib;

use Dba;
use Lib\Interfaces\Model;
use ReflectionClass;
use ReflectionException;

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

    /**
     * @param $fields
     * @param $values
     * @return array
     */
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
     * @param integer $object_id
     * @return Metadata\Repository\MetadataField
     */
    public function findById($object_id)
    {
        $rows = $this->findBy(array('id'), array($object_id));

        return reset($rows);
    }

    /**
     * @param string $table
     * @param array $field
     * @param array|string $value
     * @return array
     */
    private function getRecords($table, $field = array(), $value = null)
    {
        $data = array();
        $sql  = $this->assembleQuery($table, $field);

        $statement = Dba::read($sql, is_array($value) ? $value : array($value));
        while ($object = Dba::fetch_object($statement, $this->modelClassName)) {
            $data[$object->getId()] = $object;
        }

        return $data;
    }

    /**
     *
     * @param string $name
     * @param array $arguments
     * @return array
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

        return array();
    }

    /**
     * @return string
     */
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

    /**
     * @param DatabaseObject $object
     * @throws ReflectionException
     */
    public function add(DatabaseObject $object)
    {
        $properties = $object->getDirtyProperties();
        $this->setPrivateProperty(
                $object,
                'id',
                $this->insertRecord($properties)
        );
    }

    /**
     * @param DatabaseObject $object
     */
    public function update(DatabaseObject $object)
    {
        if ($object->isDirty()) {
            $properties = $object->getDirtyProperties();
            $this->updateRecord($object->getId(), $properties);
        }
    }

    /**
     * @param DatabaseObject $object
     */
    public function remove(DatabaseObject $object)
    {
        $id = $object->getId();
        $this->deleteRecord($id);
    }

    /**
     * @param $properties
     * @return string|null
     */
    protected function insertRecord($properties)
    {
        $sql = 'INSERT INTO ' . $this->getTableName() . ' (' . implode(',', array_keys($properties)) . ')'
                . ' VALUES(' . implode(',', array_fill(0, count($properties), '?')) . ')';
        Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );

        return Dba::insert_id();
    }

    /**
     * @param integer $object_id
     * @param $properties
     */
    protected function updateRecord($object_id, $properties)
    {
        $sql = 'UPDATE ' . $this->getTableName()
                . ' SET ' . implode(',', $this->getKeyValuePairs($properties))
                . ' WHERE id = ?';
        $properties[] = $object_id;
        Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );
    }

    /**
     * @param integer $object_id
     */
    protected function deleteRecord($object_id)
    {
        $sql = 'DELETE FROM ' . $this->getTableName()
                . ' WHERE id = ?';
        Dba::write($sql, array($object_id));
    }

    /**
     * @param $properties
     * @return array
     */
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
     * @throws ReflectionException
     */
    protected function setPrivateProperty(Model $object, $property, $value)
    {
        $reflectionClass    = new ReflectionClass(get_class($object));
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
        if ($table == 'metadata_field') {
            $sql .= ' ORDER BY `metadata_field`.`name`';
        }

        return $sql;
    }

    /**
     * camelCaseToUnderscore
     * @param  string $string
     * @return string
     */
    public function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $string));
    }
} // end repository
