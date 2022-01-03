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

namespace Ampache\Module\Util\OAuth;

use Ampache\Module\System\Core;

/**
 * Class OAuthUtil
 */
class OAuthUtil
{
    /**
     * @param $input
     * @return array|string|string[]
     */
    public static function urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return array_map(
                static function ($value) {
                    return OAuthUtil::urlencode_rfc3986($value);
                },
                $input
            );
        } elseif (is_scalar($input)) {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
        } else {
            return '';
        }
    }

    // This decode function isn't taking into consideration the above
    // modifications to the encoding process. However, this method doesn't
    // seem to be used anywhere so leaving it as is.
    /**
     * @param string $string
     * @return string
     */
    public static function urldecode_rfc3986($string)
    {
        return urldecode($string);
    }

    /**
     * Utility function for turning the Authorization: header into parameters.
     * Has to do some unescaping and can filter out any non-oauth parameters if needed (default behaviour)
     * May 28th, 2010 - method updated to tjerk.meesters for a speed improvement.
     * see http://code.google.com/p/oauth/issues/detail?id=163
     * @param $header
     * @param boolean $oauth_parameters
     * @return array
     */
    public static function split_header($header, $oauth_parameters = true)
    {
        $params = array();
        if (preg_match_all('/(' . ($oauth_parameters ? 'oauth_' : '') . '[a-z_-]*)=(:?"([^"]*)"|([^,]*))/',
            $header, $matches)) {
            foreach ($matches[1] as $i => $h) {
                $params[$h] = OAuthUtil::urldecode_rfc3986(empty($matches[3][$i]) ? $matches[4][$i] : $matches[3][$i]);
            }
            if (isset($params['realm'])) {
                unset($params['realm']);
            }
        }

        return $params;
    }

    // helper to try to sort out headers for people who aren't running apache

    /**
     * @return array
     */
    public static function get_headers()
    {
        if (function_exists('apache_request_headers')) {
            // we need this to get the actual Authorization: header
            // because apache tends to tell us it doesn't exist
            $headers = apache_request_headers();

            // sanitize the output of apache_request_headers because
            // we always want the keys to be Cased-Like-This and arh()
            // returns the headers in the same case as they are in the
            // request
            $out = array();
            foreach ($headers as $key => $value) {
                $key       = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
                $out[$key] = $value;
            }
        } else {
            // otherwise we don't have apache and are just going to have to hope
            // that $_SERVER actually contains what we need
            $out = array();
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $out['Content-Type'] = Core::get_server('CONTENT_TYPE');
            }
            if (isset($_ENV['CONTENT_TYPE'])) {
                $out['Content-Type'] = $_ENV['CONTENT_TYPE'];
            }

            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == "HTTP_") {
                    // this is chaos, basically it is just there to capitalize the first
                    // letter of every word that is not an initial HTTP and strip HTTP
                    // code from przemek
                    $key       = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                    $out[$key] = $value;
                }
            }
        }

        return $out;
    }

    // This function takes a input like a=b&a=c&d=e and returns the parsed
    // parameters like this
    // array('a' => array('b', 'c'), 'd' => 'e')
    /**
     * @param $input
     * @return array
     */
    public static function parse_parameters($input)
    {
        if (!isset($input) || !$input) {
            return array();
        }

        $pairs = explode('&', $input);

        $parsed_parameters = array();
        foreach ($pairs as $pair) {
            if (strpos((string)$pair, '=')) {
                $split     = explode('=', $pair, 2);
                $parameter = OAuthUtil::urldecode_rfc3986($split[0]);
                $value     = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

                if (isset($parsed_parameters[$parameter])) {
                    // We have already received parameter(s) with this name, so add to the list
                    // of parameters with this name

                    if (is_scalar($parsed_parameters[$parameter])) {
                        // This is the first duplicate, so transform scalar (string) into an array
                        // so we can add the duplicates
                        $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
                    }

                    $parsed_parameters[$parameter][] = $value;
                } else {
                    $parsed_parameters[$parameter] = $value;
                }
            }
        }

        return $parsed_parameters;
    }

    /**
     * @param $params
     * @return string
     */
    public static function build_http_query($params)
    {
        if (!$params) {
            return '';
        }

        // Urlencode both keys and values
        $keys   = OAuthUtil::urlencode_rfc3986(array_keys($params));
        $values = OAuthUtil::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                // June 12th, 2010 - changed to sort because of issue 164 by hidetaka
                sort($value, SORT_STRING);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }
}
