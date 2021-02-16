<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Module\WebDav;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Media;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Sabre\DAV;

/**
 * This class wrap Ampache albums and artist to WebDAV directories.
 */
class WebDavDirectory extends DAV\Collection
{
    private $libitem;

    /**
     * @param library_item $libitem
     */
    public function __construct(library_item $libitem)
    {
        $this->libitem = $libitem;
        $this->libitem->format();
    }

    /**
     * getChildren
     * @return array
     * @throws DAV\Exception\NotFound
     */
    public function getChildren()
    {
        debug_event(self::class, 'Directory getChildren', 5);
        $children = array();
        $childs   = $this->libitem->get_childrens();
        foreach ($childs as $key => $child) {
            if (is_string($key)) {
                foreach ($child as $schild) {
                    $children[] = WebDavDirectory::getChildFromArray($schild);
                }
            } else {
                $children[] = WebDavDirectory::getChildFromArray($child);
            }
        }

        return $children;
    }

    /**
     * getChild
     * @param string $name
     * @return WebDavFile|WebDavDirectory
     */
    public function getChild($name)
    {
        // Clean song name
        if (get_class($this->libitem) === Album::class) {
            $splitname = explode('-', (string)$name, 3);
            $name      = trim((string)$splitname[count($splitname) - 1]);
            $nameinfo  = pathinfo($name);
            $name      = $nameinfo['filename'];
        }
        debug_event(self::class, 'Directory getChild: ' . $name, 5);
        $matches = $this->libitem->search_childrens($name);
        // Always return first match
        // Warning: this means that two items with the same name will not be supported for now
        if (count($matches) > 0) {
            return WebDavDirectory::getChildFromArray($matches[0]);
        }

        throw new DAV\Exception\NotFound('The child with name: ' . $name . ' could not be found');
    }

    /**
     * getChildFromArray
     * @param $array
     * @return WebDavFile|WebDavDirectory
     */
    public static function getChildFromArray($array)
    {
        $class_name = ObjectTypeToClassNameMapper::map($array['object_type']);
        $libitem    = new $class_name($array['object_id']);
        if (!$libitem->id) {
            throw new DAV\Exception\NotFound('The library item `' . $array['object_type'] . '` with id `' . $array['object_id'] . '` could not be found');
        }

        if ($libitem instanceof Media) {
            return new WebDavFile($libitem);
        } else {
            return new WebDavDirectory($libitem);
        }
    }

    /**
     * childExists
     * @param string $name
     * @return boolean
     */
    public function childExists($name)
    {
        $matches = $this->libitem->search_childrens($name);

        return (count($matches) > 0);
    }

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        return (string)$this->libitem->get_fullname();
    }
}
