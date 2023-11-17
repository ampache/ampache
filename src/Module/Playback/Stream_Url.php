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
     * parse
     *
     * Takes an url and parses out all the chewy goodness.
     * @param string $url
     * @return array
     */
    public static function parse($url)
    {
        if (empty($url)) {
            return array();
        }
        if (AmpConfig::get('stream_beautiful_url')) {
            $posargs = strpos($url, '/play/');
            if ($posargs !== false) {
                $argsstr = substr($url, $posargs + 6);
                $url     = substr($url, 0, $posargs + 6) . 'index.php?';
                $args    = explode('/', $argsstr);
                $a_count = count($args);
                for ($i = 0; $i < $a_count; $i += 2) {
                    if ($i > 0) {
                        $url .= '&';
                    }
                    $url .= $args[$i] . '=' . $args[$i + 1];
                }
            }
        }

        $query    = (string)parse_url($url, PHP_URL_QUERY);
        $elements = explode('&', $query);
        $results  = array();

        $results['base_url'] = $url;

        if (!empty($elements)) {
            foreach ($elements as $element) {
                if (strpos((string)$element, '=')) {
                    list($key, $value) = explode('=', $element);
                    switch ($key) {
                        case 'oid':
                            $key = 'id';
                            break;
                        case 'video':
                            if (make_bool($value)) {
                                $results['type'] = 'video';
                            }
                            break;
                        case 'demo_id':
                            if (make_bool($value)) {
                                $results['type'] = 'democratic';
                            }
                            break;
                        case 'random_id':
                            if (make_bool($value)) {
                                $results['type'] = 'random';
                            }
                            break;
                    }
                    if (!empty($value)) {
                        $results[$key] = $value;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * add_options
     *
     * Add options to an existing stream url.
     * @param string $url
     * @param string $options
     */
    public static function add_options($url, $options): string
    {
        if (AmpConfig::get('stream_beautiful_url')) {
            // We probably want beautiful url to have a real mp3 filename at the end.
            // Add the new options before the filename

            $curel = explode('/', $url);
            $newel = explode('&', $options);

            if (count($curel) > 2) {
                foreach ($newel as $urlParameter) {
                    if (strpos((string)$urlParameter, '=')) {
                        $element = explode('=', $urlParameter);
                        array_splice($curel, count($curel) - 2, 0, $element);
                    }
                }
                $url = implode('/', $curel);
            }
        } else {
            $pos = strpos($url, '&');
            if ($pos !== false) {
                // Insert options after the ssid
                $url = substr($url, 0, $pos) . $options . substr($url, $pos);
            } else {
                $url .= $options;
            }
        }

        return $url;
    }

    /**
     * format
     * This format the string url according to settings.
     * @param string $url
     */
    public static function format($url): string
    {
        if (AmpConfig::get('stream_beautiful_url')) {
            $url = str_replace('index.php?&', '', $url);
            $url = str_replace('index.php?', '', $url);
            $url = str_replace('&', '/', $url);
            $url = str_replace('=', '/', $url);
        }

        return $url;
    }

    /**
     * get_title
     * Get a translated title for the webplayer
     * @param string $url
     */
    public static function get_title($url): string
    {
        $urlinfo = self::parse($url);
        $type    = $urlinfo['type'] ?? 'URL-Add';
        switch ($type) {
            case 'random':
                return T_('Random');
            case 'democratic':
                return T_('Democratic');
            default:
                return $type;
        }
    }
}
