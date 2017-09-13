<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

use Sabre\DAV;

/**
 * WebDAV Directory Class
 *
 * This class wrap Ampache albums and artist to WebDAV directories.
 *
 */
class WebDAV_Directory extends DAV\Collection
{
    private $libitem;

    public function __construct(library_item $libitem)
    {
        $this->libitem = $libitem;
        $this->libitem->format();
    }

    public function getChildren()
    {
        debug_event('webdav', 'Directory getChildren', 5);
        $children = array();
        $childs   = $this->libitem->get_childrens();
        foreach ($childs as $key => $child) {
            if (is_string($key)) {
                foreach ($child as $schild) {
                    $children[] = WebDAV_Directory::getChildFromArray($schild);
                }
            } else {
                $children[] = WebDAV_Directory::getChildFromArray($child);
            }
        }

        return $children;
    }

    public function getChild($name)
    {
        // Clean song name
        if (strtolower(get_class($this->libitem)) === "album") {
            $splitname = explode('-', $name, 3);
            $name      = trim($splitname[count($splitname) - 1]);
            $nameinfo  = pathinfo($name);
            $name      = $nameinfo['filename'];
        }
        debug_event('webdav', 'Directory getChild: ' . $name, 5);
        $matches = $this->libitem->search_childrens($name);
        // Always return first match
        // Warning: this means that two items with the same name will not be supported for now
        if (count($matches) > 0) {
            return WebDAV_Directory::getChildFromArray($matches[0]);
        }

        throw new DAV\Exception\NotFound('The child with name: ' . $name . ' could not be found');
        ;
    }

    public static function getChildFromArray($array)
    {
        $libitem = new $array['object_type']($array['object_id']);
        if (!$libitem->id) {
            throw new DAV\Exception\NotFound('The library item `' . $array['object_type'] . '` with id `' . $array['object_id'] . '` could not be found');
        }

        if ($libitem instanceof media) {
            return new WebDAV_File($libitem);
        } else {
            return new WebDAV_Directory($libitem);
        }
    }

    public function childExists($name)
    {
        $matches = $this->libitem->search_childrens($name);

        return (count($matches) > 0);
    }

    public function getName()
    {
        return $this->libitem->get_fullname();
    }
}
