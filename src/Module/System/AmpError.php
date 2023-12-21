<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\System;

/**
 * Error class
 *
 * This is the basic error class, its better now that we can use php5
 * hello static functions and variables
 */
class AmpError
{
    private static $state = false; // set to one when an error occurs

    public static $errors = array(); // Errors array key'd array with errors that have occurred

    /**
     * __destruct
     * This saves all of the errors that are left into the session
     */
    public function __destruct()
    {
        foreach (self::$errors as $key => $error) {
            $_SESSION['errors'][$key] = $error;
        }
    }

    /**
     * add
     * This is a public static function it adds a new error message to the array
     * It can optionally clobber rather then adding to the error message
     * @param string $name
     * @param string $message
     * @param int $clobber
     */
    public static function add($name, $message, $clobber = 0): void
    {
        // Make sure its set first
        if (!isset(AmpError::$errors[$name])) {
            AmpError::$errors[$name]   = $message;
            AmpError::$state           = true;
            $_SESSION['errors'][$name] = $message;
        } elseif ($clobber) {
            // They want us to clobber it
            AmpError::$state           = true;
            AmpError::$errors[$name]   = $message;
            $_SESSION['errors'][$name] = $message;
        } else {
            // They want us to append the error, add a BR\n and then the message
            AmpError::$state = true;
            AmpError::$errors[$name] .= "<br />\n" . $message;
            $_SESSION['errors'][$name] .= "<br />\n" . $message;
        }

        // If on SSE worker, output the error directly.
        if (defined('SSE_OUTPUT')) {
            echo "data: display_sse_error('" . addslashes($message) . "')\n\n";
            ob_flush();
            flush();
        }
    }

    /**
     * occurred
     * This returns true / false if an error has occurred anywhere
     */
    public static function occurred(): bool
    {
        if (self::$state == '1') {
            return true;
        }

        return false;
    }

    /**
     * get
     * This returns an error by name
     * @param string $name
     */
    public static function get($name): string
    {
        if (!isset(AmpError::$errors[$name])) {
            return '';
        }

        return AmpError::$errors[$name];
    }

    /**
     * display
     * This prints the error out with a standard Error class span
     * Ben Goska: Renamed from print to display, print is reserved
     * @param string $name
     */
    public static function display($name): string
    {
        // Be smart about this, if no error don't print
        if (isset(AmpError::$errors[$name])) {
            return self::getErrorsFormatted($name);
        }

        return '';
    }

    public static function getErrorsFormatted(string $name): string
    {
        if (isset(AmpError::$errors[$name])) {
            return '<p class="alert alert-danger">' . T_(AmpError::$errors[$name]) . '</p>';
        }

        return '';
    }
}
