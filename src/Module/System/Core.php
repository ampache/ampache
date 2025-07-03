<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Exception;

/**
 * Core Class
 *
 * This is really just a namespace class, it's full of static functions
 * would be replaced by a namespace library once that exists in php
 */
class Core
{
    /**
     * get_global
     * Return a $GLOBAL variable instead of calling directly
     */
    public static function get_global(string $variable): ?User
    {
        return $GLOBALS[$variable] ?? null;
    }

    /**
     * get_request
     * Return a $REQUEST variable instead of calling directly
     * @deprecated Use RequestParser
     */
    public static function get_request(string $variable): string
    {
        if (!array_key_exists($variable, $_REQUEST)) {
            return '';
        }

        return scrub_in((string) $_REQUEST[$variable]);
    }

    /**
     * get_get
     * Return a $GET variable instead of calling directly
     */
    public static function get_get(string $variable): string
    {
        if (!array_key_exists($variable, $_GET)) {
            return '';
        }

        return scrub_in((string) $_GET[$variable]);
    }

    /**
     * get_cookie
     * Return a $COOKIE variable instead of calling directly
     * @deprecated Not in use
     */
    public static function get_cookie(string $variable): string
    {
        if (!array_key_exists($variable, $_COOKIE)) {
            return '';
        }

        return scrub_in((string) $_COOKIE[$variable]);
    }

    /**
     * get_server
     * Return a $SERVER variable instead of calling directly
     */
    public static function get_server(string $variable): string
    {
        if (!array_key_exists($variable, $_SERVER)) {
            return '';
        }

        return scrub_in((string) $_SERVER[$variable]);
    }

    /**
     * get_post
     * Return a $POST variable instead of calling directly
     */
    public static function get_post(string $variable): string
    {
        if (!array_key_exists($variable, $_POST)) {
            return '';
        }

        return scrub_in((string) $_POST[$variable]);
    }

    /**
     * get_user_ip
     * check for the ip of the request
     *
     * @todo make dynamic and testable
     */
    public static function get_user_ip(): string
    {
        // get the x forward if it's valid
        if (filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR') && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            return filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
        }

        return (filter_has_var(INPUT_SERVER, 'REMOTE_ADDR'))
            ? filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: ''
            : '';
    }

    /**
     * form_register
     * This registers a form with a SID, inserts it into the session
     * variables and then returns a string for use in the HTML form
     */
    public static function form_register(string $name, string $type = 'post'): string
    {
        // Make ourselves a nice little sid
        $sid    = md5(uniqid((string)rand(), true));
        $window = AmpConfig::get('session_length', 3600);
        $expire = time() + $window;

        // Register it
        $_SESSION['forms'][$sid] = [
            'name' => $name,
            'expire' => $expire
        ];
        if (!isset($_SESSION['forms'][$sid])) {
            debug_event(self::class, "Form $sid not found in session, failed to register!", 2);
        } else {
            debug_event(self::class, "Registered $type form $name with SID $sid and expiration $expire ($window seconds from now)", 5);
        }

        // end switch on type

        return match ($type) {
            'get' => $sid,
            default => '<input type="hidden" name="form_validation" value="' . $sid . '" />',
        };
    }

    /**
     * gen_secure_token
     *
     * This generates a cryptographically secure token.
     * Returns a token of the required bytes length, as a string. Returns false
     * if it could not generate a cryptographically secure token.
     * @throws Exception
     */
    public static function gen_secure_token(int $length): ?string
    {
        if ($length < 1) {
            return null;
        } elseif (function_exists('random_bytes')) {
            $buffer = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $buffer = openssl_random_pseudo_bytes($length);
        } elseif (file_exists('/dev/random') && is_readable('/dev/random')) {
            $buffer = file_get_contents('/dev/random', false, null, -1, $length);
        } else {
            return null;
        }

        return bin2hex((string)$buffer);
    }

    /**
     * image_dimensions
     * This returns the dimensions of the passed song of the passed type
     * returns an empty array if PHP-GD is not currently installed, returns
     * false on error
     *
     * @param string $image_data
     * @return array{width: int, height: int}
     */
    public static function image_dimensions(string $image_data): array
    {
        $empty = [
            'width' => 0,
            'height' => 0,
        ];
        if (!function_exists('imagecreatefromstring')) {
            return $empty;
        }

        if (empty($image_data)) {
            debug_event(self::class, "Cannot create image from empty data", 2);

            return $empty;
        }

        $image = imagecreatefromstring($image_data);
        if (!$image) {
            return $empty;
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        if (
            $width > 1 &&
            $height > 1
        ) {
            return [
                'width' => $width,
                'height' => $height
            ];
        }

        return $empty;
    }

    /**
     * is_readable
     *
     * Replacement function because PHP's is_readable is buggy:
     * https://bugs.php.net/bug.php?id=49620
     */
    public static function is_readable(string $path): bool
    {
        if (!$path) {
            return false;
        }
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }
        if (file_exists($path)) {
            if (is_dir($path)) {
                $handle = opendir($path);
                if ($handle === false) {
                    return false;
                }
                closedir($handle);

                return true;
            }

            $handle = @fopen($path, 'rb');
            if ($handle === false) {
                return false;
            }
            fclose($handle);

            return true;
        }

        return false;
    }

    /**
     * get_filesize
     * Get a file size. This because filesize() doesn't work on 32-bit OS with files > 2GB
     */
    public static function get_filesize(?string $filename): int
    {
        if (!$filename || !file_exists($filename)) {
            return 0;
        }
        $size = filesize($filename);
        if ($size === false) {
            $filepointer = fopen($filename, 'rb');
            if (!$filepointer) {
                return 0;
            }
            $offset = PHP_INT_MAX - 1;
            $size   = (float)$offset;
            if (!fseek($filepointer, $offset)) {
                return 0;
            }
            $chunksize = 8192;
            while (!feof($filepointer)) {
                $size += strlen((string)fread($filepointer, $chunksize));
            }
        } elseif ($size < 0) {
            // Handle overflowed integer...
            $size = sprintf("%u", $size);
        }

        return (int)$size;
    }

    /**
     * conv_lc_file
     *
     * Convert site charset filename to local charset filename for file operations
     */
    public static function conv_lc_file(string $filename): string
    {
        $lc_filename  = $filename;
        $site_charset = AmpConfig::get('site_charset', 'UTF-8');
        $lc_charset   = AmpConfig::get('lc_charset');
        if ($lc_charset && $lc_charset != $site_charset) {
            if (function_exists('iconv')) {
                $lc_filename = iconv($site_charset, $lc_charset, $filename);
            }
        }

        return $lc_filename ?: $filename;
    }

    /**
     * is_session_started
     *
     * Universal function for checking session status.
     */
    public static function is_session_started(): bool
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return !(session_id() === '');
            }
        }

        return false;
    }

    /**
     * get_reloadutil
     */
    public static function get_reloadutil(): string
    {
        $play_type = AmpConfig::get('play_type');

        return ($play_type == "stream" || $play_type == "democratic" || !AmpConfig::get('ajax_load'))
            ? "reloadUtil"
            : "reloadDivUtil";
    }

    /**
     * requests_options
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function requests_options(array $options = []): array
    {
        if (!isset($options['proxy'])) {
            if (AmpConfig::get('proxy_host') && AmpConfig::get('proxy_port')) {
                $proxy   = [];
                $proxy[] = AmpConfig::get('proxy_host') . ':' . AmpConfig::get('proxy_port');
                if (AmpConfig::get('proxy_user')) {
                    $proxy[] = AmpConfig::get('proxy_user');
                    $proxy[] = AmpConfig::get('proxy_pass');
                }

                $options['proxy'] = $proxy;
            }
        }

        return $options;
    }

    /**
     * get_tmp_dir
     */
    public static function get_tmp_dir(): string
    {
        if (AmpConfig::get('tmp_dir_path')) {
            return rtrim((string)AmpConfig::get('tmp_dir_path'), DIRECTORY_SEPARATOR);
        }
        if (function_exists('sys_get_temp_dir')) {
            $tmp_dir = sys_get_temp_dir();
        } elseif (strpos(PHP_OS, 'WIN') === 0) {
            $tmp_dir = $_ENV['TMP'];
            if (!isset($tmp_dir)) {
                $tmp_dir = 'C:\Windows\Temp';
            }
        } else {
            $tmp_dir = @$_ENV['TMPDIR'];
            if (!isset($tmp_dir)) {
                $tmp_dir = '/tmp';
            }
        }

        return $tmp_dir;
    }
}
