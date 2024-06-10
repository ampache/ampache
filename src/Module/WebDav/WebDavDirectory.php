<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\WebDav;

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Media;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Sabre\DAV;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Node;

/**
 * This class wrap Ampache albums and artist to WebDAV directories.
 */
class WebDavDirectory extends DAV\Collection
{
    private library_item $libitem;

    public function __construct(library_item $libitem)
    {
        $this->libitem = $libitem;
    }

    /**
     * @return list<Node>
     *
     * @throws NotFound
     */
    public function getChildren(): array
    {
        //debug_event(self::class, 'Directory getChildren', 5);
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
     * @param string $name
     *
     * @throws NotFound
     */
    public function getChild($name): Node
    {
        //debug_event(self::class, 'Directory getChild: ' . unhtmlentities($name), 5);
        $matches = $this->libitem->get_children(unhtmlentities($name));
        // Always return first match
        // Warning: this means that two items with the same name will not be supported for now
        if (!empty($matches)) {
            return WebDavDirectory::getChildFromArray($matches[0]);
        }

        throw new NotFound('The child with name: ' . $name . ' could not be found');
    }

    /**
     * @param array{object_type: string, object_id: int} $array
     */
    public static function getChildFromArray(array $array): Node
    {
        $className = ObjectTypeToClassNameMapper::map($array['object_type']);
        /** @var library_item $libitem */
        $libitem = new $className($array['object_id']);
        if ($libitem->isNew()) {
            throw new NotFound('The library item `' . $array['object_type'] . '` with id `' . $array['object_id'] . '` could not be found');
        }

        if ($libitem instanceof Media) {
            return new WebDavFile($libitem);
        } else {
            return new WebDavDirectory($libitem);
        }
    }

    /**
     * @param string $name
     */
    public function childExists($name): bool
    {
        $matches = $this->libitem->get_children($name);

        return !empty($matches);
    }

    public function getName(): string
    {
        return (string) $this->libitem->get_fullname();
    }
}
