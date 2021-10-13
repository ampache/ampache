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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;

/**
 * A collection of methods related to cache-busting assets
 */
class AssetCache
{
    private static string $cacheDir = '/cached_assets/';
    private static string $cacheDirURL;
    private static string $cacheDirPath;

    private static function set_cache_dir()
    {
        self::$cacheDirURL  = AmpConfig::get('web_path') . self::$cacheDir;
        self::$cacheDirPath = Core::get_server('DOCUMENT_ROOT') . self::$cacheDir;
    }

    /**
     * This uses the MD5 hash of a file to create a unique cached version, to avoid 'just clear your browser cache' issues
     * @param string $path
     * @return string
     */
    public static function get_url(string $path)
    {
        self::set_cache_dir();

        $originalFileURL       = $path;
        $originalFilePath      = Core::get_server('DOCUMENT_ROOT') . str_replace(AmpConfig::get('web_path'), '', $path);
        $originalFilePathArray = pathinfo($path);

        $cachedFileURL  = self::$cacheDirURL . $originalFilePathArray['filename'] . '-' . md5_file($originalFilePath) . '.' . $originalFilePathArray['extension'];
        $cachedFilePath = self::$cacheDirPath . $originalFilePathArray['filename'] . '-' . md5_file($originalFilePath) . '.' . $originalFilePathArray['extension'];

        if (!file_exists($cachedFilePath)) {
            self::copy_file($originalFilePath);
        }

        if (file_exists($cachedFilePath)) {
            return $cachedFileURL;
        }

        // if all else fails return original file
        return $originalFileURL;
    }

    private static function copy_file(string $path)
    {
        self::create_cache_dir();

        $filePath      = str_replace(AmpConfig::get('web_path'), Core::get_server('DOCUMENT_ROOT'), $path);
        $filepathArray = pathinfo($path);

        if (file_exists($filePath)) {
            $cachedVersion = self::$cacheDirPath . $filepathArray['filename'] . '-' . md5_file($filePath) . '.' . $filepathArray['extension'];
            copy($filePath, $cachedVersion);
        }
    }

    private static function create_cache_dir()
    {
        if (!file_exists(self::$cacheDirPath)) {
            mkdir(self::$cacheDirPath, 0755);
        }
    }

    public static function clear_cache()
    {
        self::set_cache_dir();

        $files = glob(self::$cacheDirPath . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
