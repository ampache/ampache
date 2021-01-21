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
 * WebDAV File Class
 *
 * This class wrap Ampache songs to WebDAV files.
 *
 */
class WebDAV_File extends DAV\File
{
    private $libitem;

    /**
     * WebDAV_File constructor.
     * @param media $libitem
     */
    public function __construct(media $libitem)
    {
        $this->libitem = $libitem;
        $this->libitem->format();
    }

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        return $this->libitem->f_file;
    }

    /**
     * get
     * @return resource|null
     */
    public function get()
    {
        debug_event(self::class, 'File get', 5);
        // Only media associated to a local catalog is supported
        if ($this->libitem->catalog) {
            $catalog = Catalog::create_from_id($this->libitem->catalog);
            if ($catalog->get_type() === 'local') {
                return fopen($this->libitem->file, 'r');
            } else {
                debug_event(self::class, 'Catalog associated to the media is not local. This is currently unsupported.', 3);
            }
        } else {
            debug_event(self::class, 'No catalog associated to the media.', 3);
        }

        return null;
    }

    /**
     * getSize
     * @return integer
     */
    public function getSize()
    {
        return $this->libitem->size;
    }

    /**
     * getETag
     * @return string
     */
    public function getETag()
    {
        return md5(get_class($this->libitem) . "_" . $this->libitem->id . "_" . $this->libitem->update_time);
    }
} // end webdav_file.class
