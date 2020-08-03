<?php
declare(strict_types=0);
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

// A magical class filled with ponies

class memory_object
{
    private $_data = array();
    public $properties;

    /**
     * memory_object constructor.
     * @param $data
     */
    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->properties)) {
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->properties)) {
            $this->_data[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @return boolean|mixed|null
     */
    public function __get($name)
    {
        if (!in_array($name, $this->properties)) {
            return false;
        }

        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }
} // end memory_object.class
