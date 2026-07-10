<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Media;
use Override;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Node;

/**
 * This class wrap Ampache albums and artist to WebDAV directories.
 */
class WebDavDirectory extends Collection
{
    public function __construct(private readonly WebDavDirectoryInterface $libitem) {}

    /**
     * @param array{object_type: LibraryItemEnum, object_id: int} $array
     * @throws NotFound
     */
    public static function getChildFromArray(array $array): Node
    {
        $className = ObjectTypeToClassNameMapper::map($array['object_type']->value);
        /** @var container_item $libitem */
        $libitem = new $className($array['object_id']);
        if ($libitem->isNew()) {
            throw new NotFound(self::class . ' The library item `' . $array['object_type']->value . '` with id `' . $array['object_id'] . '` could not be found');
        }

        if ($libitem instanceof Media) {
            return new WebDavFile($libitem);
        }

        if ($libitem instanceof WebDavDirectoryInterface) {
            return new WebDavDirectory($libitem);
        }

        throw new NotFound(self::class . ' The child with name: ' . $libitem->get_fullname() . ' could not be created');
    }

    /**
     * @param string $name
     */
    #[Override]
    public function childExists($name): bool
    {
        return $this->libitem->has_children($name);
    }

    /**
     * @param string $name
     * @throws NotFound
     */
    #[Override]
    public function getChild($name): Node
    {
        //debug_event(self::class, 'Directory getChild: ' . unhtmlentities($name), 5);
        $folder = Catalog::get_child(unhtmlentities($name), $this->libitem->getCatalog(), $this->libitem->getId());
        if ($folder instanceof WebDavDirectoryInterface) {
            return new WebDavDirectory($folder);
        }

        if ($folder !== null) {
            return new WebDavFile($folder);
        }

        throw new NotFound(self::class . ' The child with name: ' . $name . ' could not be found');
    }

    /**
     * @return list<Node>
     * @throws NotFound
     */
    public function getChildren(): array
    {
        //debug_event(self::class, 'Directory getChildren', 5);
        $children = [];
        $items    = $this->libitem->get_childrens();
        foreach ($items as $child) {
            $children[] = WebDavDirectory::getChildFromArray($child);
        }

        return $children;
    }

    public function getName(): string
    {
        return str_replace('/', '', (string) $this->libitem->get_fullname());
    }
}
