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

// A magical class filled with ponies

class memory_object
{
    private $_data = array();
    public $properties;

    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->properties)) {
                $this->_data[$key] = $value;
            }
        }
    }

    public function __set($name, $value)
    {
        if (!in_array($name, $this->properties)) {
            return false;
        }
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        if (!in_array($name, $this->properties)) {
            return false;
        }

        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }
}
