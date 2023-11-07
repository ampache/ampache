<?php
/*
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

use Ampache\Module\System\Core;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Sabre\DAV;

/**
 * This class wrap Ampache songs to WebDAV files.
 */
class WebDavFile extends DAV\File
{
    private $libitem;

    /**
     * @param Media $libitem
     */
    public function __construct(Media $libitem)
    {
        $this->libitem = $libitem;
    }

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        $nameinfo = pathinfo($this->libitem->file);

        return (string)htmlentities($nameinfo['filename'] . '.' . $nameinfo['extension']);
    }

    /**
     * get
     * @return resource|null
     */
    public function get()
    {
        //debug_event(self::class, 'File get ' . $this->libitem->file, 5);
        // Only media associated to a local catalog is supported
        if ($this->libitem->catalog) {
            $catalog = Catalog::create_from_id($this->libitem->catalog);
            if ($catalog->get_type() === 'local') {
                $filepointer = fopen(Core::conv_lc_file($this->libitem->file), 'r');

                if (!is_resource($filepointer)) {
                    debug_event(self::class, 'ERROR: unable to open file ' . $this->libitem->file, 3);

                    return null;
                }

                return $filepointer;
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
     * @return int
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
        return md5(ObjectTypeToClassNameMapper::reverseMap(get_class($this->libitem)) . "_" . $this->libitem->id . "_" . $this->libitem->update_time);
    }
}
