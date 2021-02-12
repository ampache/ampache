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

use Sabre\DAV;

/**
 * WebDAV Catalog Directory Class
 *
 * This class wrap Ampache catalogs to WebDAV directories.
 *
 */
class WebDAV_Catalog extends DAV\Collection
{
    private $catalog_id;

    /**
     * WebDAV_Catalog constructor.
     * @param integer $catalog_id
     */
    public function __construct($catalog_id = 0)
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
            $children[] = new WebDAV_Directory($artist);
        }

        return $children;
    }

    /**
     * getChild
     * @param string $name
     * @return WebDAV_File|WebDAV_Directory
     * @throws DAV\Exception\NotFound
     */
    public function getChild($name)
    {
        debug_event(self::class, 'Catalog getChild for `' . $name . '`', 5);
        $matches = Catalog::search_childrens($name, $this->catalog_id);
        debug_event(self::class, 'Found ' . count($matches) . ' childs.', 5);
        // Always return first match
        // Warning: this means that two items with the same name will not be supported for now
        if (count($matches) > 0) {
            return WebDAV_Directory::getChildFromArray($matches[0]);
        }

        throw new DAV\Exception\NotFound('The artist with name: ' . $name . ' could not be found');
    }

    /**
     * childExists
     * @param string $name
     * @return boolean
     */
    public function childExists($name)
    {
        $matches = Catalog::search_childrens($name, $this->catalog_id);

        return (count($matches) > 0);
    }

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        if ($this->catalog_id > 0) {
            $catalog = Catalog::create_from_id($this->catalog_id);

            return $catalog->name;
        }

        return AmpConfig::get('site_title');
    }
} // end webdav_catalog.class
