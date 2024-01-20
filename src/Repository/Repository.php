<?php

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

namespace Ampache\Repository;

use Ampache\Repository\Model\DatabaseObject;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Model;
use ReflectionClass;
use ReflectionException;

class Repository
{
    protected $modelClassName;

    /**
     * @param $fields
     * @param $values
     * @return array
     */
    protected function findBy($fields, $values): array
    {
        $table = $this->getTableName();

        return $this->getRecords($table, $fields, $values);
    }

    /**
     *
     * @return DatabaseObject[]
     */
    public function findAll(): array
    {
        $table = $this->getTableName();

        return $this->getRecords($table);
    }

    /**
     *
     * @param int $object_id
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
    private function getRecords($table, $field = array(), $value = null): array
    {
        $data = array();
        $sql  = $this->assembleQuery($table, $field);

        $statement = Dba::read($sql, is_array($value) ? $value : array($value));
        /** @var library_item $object */
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
     * Get database table name from Class
     */
    private function getTableName(): string
    {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $tableName = preg_replace_callback(
            '/(?<=.)([A-Z])/',
            function ($name) {
                return '_' . strtolower((string) $name[0]);
            },
            end($nameParts)
        );

        return lcfirst($tableName);
    }

    /**
     * @param DatabaseObject $object
     * @throws ReflectionException
     */
    public function add(DatabaseObject $object): void
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
    public function update(DatabaseObject $object): void
    {
        if ($object->isDirty()) {
            $properties = $object->getDirtyProperties();
            $this->updateRecord($object->getId(), $properties);
        }
    }

    /**
     * @param DatabaseObject $object
     */
    public function remove(DatabaseObject $object): void
    {
        $this->deleteRecord($object->getId());
    }

    /**
     * @param $properties
     * @return string|false
     */
    protected function insertRecord($properties)
    {
        $sql = 'INSERT INTO ' . $this->getTableName() . ' (' . implode(',', array_keys($properties)) . ") VALUES(" . implode(',', array_fill(0, count($properties), '?')) . ")";
        Dba::write(
            $sql,
            array_values($this->resolveObjects($properties))
        );

        return Dba::insert_id();
    }

    /**
     * @param int $object_id
     * @param $properties
     */
    protected function updateRecord($object_id, $properties): void
    {
        $sql = 'UPDATE `' . $this->getTableName() . '`' .
            ' SET ' . implode(',', $this->getKeyValuePairs($properties)) .
            ' WHERE `id` = ?';
        $properties[] = $object_id;
        Dba::write(
            $sql,
            array_values($this->resolveObjects($properties))
        );
    }

    /**
     * @param int $object_id
     */
    protected function deleteRecord($object_id): void
    {
        $sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `id` = ?';
        Dba::write($sql, array($object_id));
    }

    /**
     * @param $properties
     * @return array
     */
    protected function getKeyValuePairs($properties): array
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
     * @param string|false $value
     * @throws ReflectionException
     */
    protected function setPrivateProperty(Model $object, $property, $value): void
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
    protected function resolveObjects(array $properties): array
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
     */
    public function assembleQuery($table, $fields): string
    {
        $sql = "SELECT * FROM `$table`";
        if (!empty($fields)) {
            $sql .= ' WHERE ';
            $sqlParts = array();
            foreach ($fields as $field) {
                $sqlParts[] = '`' . $this->camelCaseToUnderscore($field) . '` = ?';
            }
            $sql .= implode(' AND ', $sqlParts);
        }
        if ($table == 'metadata_field') {
            $sql .= ' ORDER BY `metadata_field`.`name`';
        }

        return $sql;
    }

    /**
     * camelCaseToUnderscore
     * @param string $string
     */
    public function camelCaseToUnderscore($string): string
    {
        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $string));
    }
}
