<?php

namespace Sabre\HTTP;

/**
 * URL utility class
 *
 * This class provides methods to deal with encoding and decoding url (percent encoded) strings.
 *
 * It was not possible to use PHP's built-in methods for this, because some clients don't like
 * encoding of certain characters.
 *
 * Specifically, it was found that GVFS (gnome's webdav client) does not like encoding of ( and
 * ). Since these are reserved, but don't have a reserved meaning in url, these characters are
 * kept as-is.
 *
 * @copyright Copyright (C) 2009-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class URLUtil {

    /**
     * Encodes the path of a url.
     *
     * slashes (/) are treated as path-separators.
     *
     * @param string $path
     * @return string
     */
    static function encodePath($path) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)\/:@])/',function($match) {

            return '%'.sprintf('%02x',ord($match[0]));

        }, $path);

    }

    /**
     * Encodes a 1 segment of a path
     *
     * Slashes are considered part of the name, and are encoded as %2f
     *
     * @param string $pathSegment
     * @return string
     */
    static function encodePathSegment($pathSegment) {

        return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\):@])/',function($match) {

            return '%'.sprintf('%02x',ord($match[0]));

        }, $pathSegment);
    }

    /**
     * Decodes a url-encoded path
     *
     * @param string $path
     * @return string
     */
    static function decodePath($path) {

        return self::decodePathSegment($path);

    }

    /**
     * Decodes a url-encoded path segment
     *
     * @param string $path
     * @return string
     */
    static function decodePathSegment($path) {

        $path = rawurldecode($path);
        $encoding = mb_detect_encoding($path, ['UTF-8','ISO-8859-1']);

        switch($encoding) {

            case 'ISO-8859-1' :
                $path = utf8_encode($path);

        }

        return $path;

    }

    /**
     * Returns the 'dirname' and 'basename' for a path.
     *
     * The reason there is a custom function for this purpose, is because
     * basename() is locale aware (behaviour changes if C locale or a UTF-8 locale is used)
     * and we need a method that just operates on UTF-8 characters.
     *
     * In addition basename and dirname are platform aware, and will treat backslash (\) as a
     * directory separator on windows.
     *
     * This method returns the 2 components as an array.
     *
     * If there is no dirname, it will return an empty string. Any / appearing at the end of the
     * string is stripped off.
     *
     * @param string $path
     * @return array
     */
    static function splitPath($path) {

        $matches = [];
        if(preg_match('/^(?:(.*)\/+)?([^\/]+)\/?$/u',$path,$matches)) {
            return [$matches[1], $matches[2]];
        } else {
            return [null, null];
        }

    }

    /**
     * Resolves relative urls, like a browser would.
     *
     * This function takes a basePath, which itself _may_ also be relative, and
     * then applies the relative path on top of it.
     *
     * @param string $basePath
     * @param string $newPath
     * @return string
     */
    static function resolve($basePath, $newPath) {

        $base = parse_url($basePath);
        $delta = parse_url($newPath);

        $pick = function($part) use ($base, $delta) {

            if (isset($delta[$part])) {
                return $delta[$part];
            } elseif (isset($base[$part])) {
                return $base[$part];
            } else {
                return null;
            }

        };

        $url = '';
        $scheme = $pick('scheme');
        $host = $pick('host');

        if ($scheme) {
            // If there's a scheme, there's also a host.
            $url=$scheme.'://' . $host;
        } elseif (!$scheme && $host) {
            // No scheme, but there is a host.
            $url = '//' . $host;
        }

        $port = $pick('port');
        if ($port) {
            // tcp port.
            $url.=':' . $port;
        }

        $path = '';
        if (isset($delta['path'])) {
            // If the path starts with a slash
            if ($delta['path'][0]==='/') {
                $path = $delta['path'];
            } else {
                // Removing last component from base path.
                $path = $base['path'];
                if (strpos($path, '/')!==false) {
                    $path = substr($path,0,strrpos($path,'/'));
                }
                $path.='/' . $delta['path'];
            }
        } else {
            $path = isset($base['path'])?$base['path']:'/';
        }
        // Removing .. and .
        $pathParts = explode('/', $path);
        $newPathParts = [];
        foreach($pathParts as $pathPart) {

            switch($pathPart) {
                case '' :
                case '.' :
                    break;
                case '..' :
                    array_pop($newPathParts);
                    break;
                default :
                    $newPathParts[] = $pathPart;
                    break;
            }
        }

        $url.='/' . implode('/', $newPathParts);

        // If the source url ended with a /, we want to preserve that.
        if (substr($path, -1) === '/' && $path!=='/') {
            $url.='/';
        }

        if (isset($delta['query'])) {
            $url.='?' . $delta['query'];
        }
        if (isset($delta['fragment'])) {
            $url.='#' . $delta['fragment'];
        }

        return $url;

    }

}
