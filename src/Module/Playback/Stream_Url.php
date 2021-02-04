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

namespace Ampache\Module\Playback;

use Ampache\Module\Util\MemoryObject;
use Ampache\Config\AmpConfig;

/**
 * Stream_URL Class
 *
 * A class for passing around an URL and associated data
 * @property string $url
 * @property string $title
 * @property string $author
 * @property string $time
 * @property string $info_url
 * @property string $image_url
 * @property string $album
 * @property string $type
 * @property string $codec
 */
class Stream_Url extends MemoryObject
{
    public $properties = array('url', 'title', 'author', 'time', 'info_url', 'image_url', 'album', 'type', 'codec');

    /**
     * add_options
     *
     * Add options to an existing stream url.
     * @param string $url
     * @param string $options
     * @return string
     */
    public static function add_options($url, $options)
    {
        if (AmpConfig::get('stream_beautiful_url')) {
            // We probably want beautiful url to have a real mp3 filename at the end.
            // Add the new options before the filename

            $curel = explode('/', $url);
            $newel = explode('&', $options);

            if (count($curel) > 2) {
                foreach ($newel as $el) {
                    if (!empty($el)) {
                        $el = explode('=', $el);
                        array_splice($curel, count($curel) - 2, 0, $el);
                    }
                }
                $url = implode('/', $curel);
            }
        } else {
            $url .= $options;
        }

        return $url;
    }

    /**
     * format
     * This format the string url according to settings.
     * @param string $url
     * @return string
     */
    public static function format($url)
    {
        if (AmpConfig::get('stream_beautiful_url')) {
            $url = str_replace('index.php?&', '', $url);
            $url = str_replace('index.php?', '', $url);
            $url = str_replace('&', '/', $url);
            $url = str_replace('=', '/', $url);
        }

        return $url;
    }
}
