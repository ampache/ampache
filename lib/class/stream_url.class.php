<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
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

// A class for passing around an URL and associated data

class Stream_URL extends memory_object
{
    public $properties = array('url', 'title', 'author', 'time', 'info_url', 'image_url', 'album', 'type', 'codec');

    /**
     * parse
     *
     * Takes an url and parses out all the chewy goodness.
     */
    public static function parse($url)
    {
        if (AmpConfig::get('stream_beautiful_url')) {
            $posargs = strpos($url, '/play/');
            if ($posargs !== false) {
                $argsstr = substr($url, $posargs + 6);
                $url = substr($url, 0, $posargs + 6) . 'index.php?';
                $args = explode('/', $argsstr);
                for ($i = 0; $i < count($args); $i += 2) {
                    if ($i > 0)
                        $url .= '&';
                    $url .= $args[$i] . '=' . $args[$i + 1];
                }
            }
        }

        $query = parse_url($url, PHP_URL_QUERY);
        $elements = explode('&', $query);
        $results = array();

        $results['base_url'] = $url;

        foreach ($elements as $element) {
            list($key, $value) = explode('=', $element);
            switch ($key) {
                case 'oid':
                    $key = 'id';
                break;
                case 'video':
                    if (make_bool($value)) {
                        $results['type'] = 'video';
                    }
                default:
                    // Nothing
                break;
            }
            $results[$key] = $value;
        }
        return $results;
    }

    /**
     * add_options
     *
     * Add options to an existing stream url.
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
