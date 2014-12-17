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

namespace lib\Metadata;

/**
 * Description of metadata
 *
 * @author raziel
 */
class Metadata
{
    /**
     * Some kind of item
     * @var library_item
     */
    protected $object;

    /**
     * Tag values
     * @var stdClass
     */
    protected $data;

    public function __construct($object)
    {
        $this->object = $object;
        $this->data = new stdClass();
    }

    public function load($tag = null)
    {
        $sql = 'SELECT metadata.data, metadata_field.name FROM metadata '
                . 'JOIN metadata_field ON metadata.field = metadata_field.id';
        if($tag) {
            $sql .= ' WHERE metadata_field.name = ?';
        }
        $statement = \Dba::read($sql, $tag);
        while($object = \Dba::fetch_object($statement, __CLASS__)) {
            $this->data[$object->getId()] = $object;
        }
        return $this->data;
    }

    public function save() {

    }

    public function get($tag)
    {
        return $this->data->$tag;
    }

    public function getAll() {
        return $this->data;
    }

    public function set($tag, $data)
    {
        $this->data->$tag = $data;
    }

    public function setAll($tags) {
        $this->data = (object) $tags;
    }

}
