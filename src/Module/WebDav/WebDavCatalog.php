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

declare(strict_types=0);

namespace Ampache\Module\WebDav;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Sabre\DAV;

/**
 * WebDAV Catalog Directory Class
 *
 * This class wrap Ampache catalogs to WebDAV directories.
 */
class WebDavCatalog extends DAV\Collection
{
    private int $catalog_id;

    public function __construct(int $catalog_id)
    {
        $this->catalog_id = $catalog_id;
    }

    /**
     * getChildren
     * @return array
     */
    public function getChildren()
    {
        $children = array();
        $catalogs = null;
        if ($this->catalog_id > 0) {
            $catalogs   = array();
            $catalogs[] = $this->catalog_id;
        }
        $artists = Catalog::get_artists($catalogs);
        foreach ($artists as $artist) {
            $children[] = new WebDavDirectory($artist);
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
        $matches = Catalog::get_children($name, $this->catalog_id);
        //debug_event(self::class, 'Catalog getChild for `' . $name . '`', 5);
        //debug_event(self::class, 'Found ' . count($matches) . ' childs.', 5);
        // Always return first match
        // Warning: this means that two items with the same name will not be supported for now TODO support folders instead of objects
        if (!empty($matches)) {
            return WebDavDirectory::getChildFromArray($matches[0]);
        }

        throw new DAV\Exception\NotFound('The artist with name: ' . $name . ' could not be found');
    }

    /**
     * childExists
     * @param string $name
     */
    public function childExists($name): bool
    {
        $matches = Catalog::get_children($name, $this->catalog_id);

        return (!empty($matches));
    }

    /**
     * getName
     */
    public function getName(): string
    {
        if ($this->catalog_id > 0) {
            $catalog = Catalog::create_from_id($this->catalog_id);

            return $catalog->name ?? '';
        }

        return AmpConfig::get('site_title');
    }
}
