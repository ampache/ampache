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

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Folder;
use Override;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Node;

/**
 * WebDAV Catalog Directory Class
 *
 * This class wrap Ampache catalogs to WebDAV directories.
 */
class WebDavCatalog extends Collection
{
    private ?int $catalog_id = null;
    private ?int $parent_id  = null;

    /**
     * @param string $name
     */
    #[Override]
    public function childExists($name): bool
    {
        return Catalog::has_children($name, $this->catalog_id, $this->parent_id);
    }

    /**
     * @param string $name
     * @throws NotFound
     */
    #[Override]
    public function getChild($name): Node
    {
        $folder = Catalog::get_child($name, $this->catalog_id, $this->parent_id);
        //debug_event(self::class, 'Catalog getChild for: `' . $name . '` catalog: ' . $this->catalog_id . ' parent: ' . $this->parent_id, 5);
        if ($folder instanceof Folder) {
            $this->catalog_id = $folder->catalog;
            $this->parent_id  = $folder->id;

            return new WebDavDirectory($folder);
        }

        throw new NotFound(self::class . ' The folder with name: ' . $name . ' could not be found');
    }

    /**
     * @return list<Node>
     * @throws NotFound
     */
    public function getChildren(): array
    {
        $items    = Catalog::get_children('/');
        $children = [];
        foreach ($items as $item) {
            $children[] = WebDavDirectory::getChildFromArray($item);
        }

        return $children;
    }

    public function getName(): string
    {
        if ($this->parent_id > 0) {
            $folder = new Folder($this->parent_id);

            if (!$folder->isNew()) {
                return $folder->name ?? '';
            }
        }

        return (string) AmpConfig::get('site_title');
    }
}
