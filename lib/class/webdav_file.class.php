<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

    public function __construct(media $libitem)
    {
        $this->libitem = $libitem;
        $this->libitem->format();
    }

    public function getName()
    {
        return $this->libitem->f_file;
    }

    public function get()
    {
        debug_event('webdav', 'File get', 5);
        // Only media associated to a local catalog is supported
        if ($this->libitem->catalog) {
            $catalog = Catalog::create_from_id($this->libitem->catalog);
            if ($catalog->get_type() === 'local') {
                return fopen($this->libitem->file, 'r');
            } else {
                debug_event('webdav', 'Catalog associated to the media is not local. This is currently unsupported.', 3);
            }
        } else {
            debug_event('webdav', 'No catalog associated to the media.', 3);
        }

        return null;
    }

    public function getSize()
    {
        return $this->libitem->size;
    }

    public function getETag()
    {
        return md5(get_class($this->libitem) . "_" . $this->libitem->id . "_" . $this->libitem->update_time);
    }
}
