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

namespace Ampache\Module\Util;

use Ampache\Module\System\Core;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * A collection of methods related to cache-busting assets
 */
class AssetCache
{
    const cacheText = '_cached_by_ampache_';

    /**
     * This uses the MD5 hash of a file to create a unique cached version, to avoid 'just clear your browser cache' issues
     * @param string $url
     * @return string
     */
    public static function get_url(string $url)
    {
        $originalArray = pathinfo($url);
        $originalURL   = $url;
        $originalPath  = self::get_path($originalURL);

        $cachedURL  = $originalArray['dirname'] . '/' . $originalArray['filename'] . self::cacheText . md5_file(self::get_path($originalURL)) . '.' . $originalArray['extension'];
        $cachedPath = self::get_path($cachedURL);

        if (!file_exists($cachedPath)) {
            self::copy_file($originalPath);
        }

        if (file_exists($cachedPath)) {
            return $cachedURL;
        }

        // if all else fails return original file
        return $url;
    }

    private static function get_path($url)
    {
        return Core::get_server('DOCUMENT_ROOT') . parse_url($url, PHP_URL_PATH);
    }

    private static function copy_file(string $path)
    {
        $pathArray = pathinfo($path);

        if (file_exists($path)) {
            $cachedVersion = $pathArray['dirname'] . '/' . $pathArray['filename'] . self::cacheText . md5_file($path) . '.' . $pathArray['extension'];
            copy($path, $cachedVersion);
        }
    }

    public static function clear_cache()
    {
        $directory = new RecursiveDirectoryIterator(Core::get_server('DOCUMENT_ROOT'));
        $iterator  = new RecursiveIteratorIterator($directory);
        $files     = new RegexIterator($iterator, '/.+' . self::cacheText . '.+/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($files as $file) {
            $file = implode("", $file);
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
