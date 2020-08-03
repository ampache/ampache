<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * Error class
 *
 * This is the basic error class, its better now that we can use php5
 * hello static functions and variables
 *
 */
class AmpError
{
    private static $state  = false; // set to one when an error occurs
    private static $errors = array(); // Errors array key'd array with errors that have occurred

    /**
     * __constructor
     * This does nothing... amazing isn't it!
     */
    private function __construct()
    {
        // Rien a faire
    } // __construct

    /**
     * __destruct
     * This saves all of the errors that are left into the session
     */
    public function __destruct()
    {
        foreach (self::$errors as $key => $error) {
            $_SESSION['errors'][$key] = $error;
        }
    } // __destruct

    /**
     * add
     * This is a public static function it adds a new error message to the array
     * It can optionally clobber rather then adding to the error message
     * @param string $name
     * @param string $message
     * @param integer $clobber
     */
    public static function add($name, $message, $clobber = 0)
    {
        // Make sure its set first
        if (!isset(AmpError::$errors[$name])) {
            AmpError::$errors[$name]      = $message;
            AmpError::$state              = true;
            $_SESSION['errors'][$name]    = $message;
        } elseif ($clobber) {
            // They want us to clobber it
            AmpError::$state              = true;
            AmpError::$errors[$name]      = $message;
            $_SESSION['errors'][$name]    = $message;
        }
        // They want us to append the error, add a BR\n and then the message
        else {
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
    } // add

    /**
     * occurred
     * This returns true / false if an error has occurred anywhere
     * @return boolean
     */
    public static function occurred()
    {
        if (self::$state == '1') {
            return true;
        }

        return false;
    } // occurred

    /**
     * get
     * This returns an error by name
     * @param string $name
     * @return string
     */
    public static function get($name)
    {
        if (!isset(AmpError::$errors[$name])) {
            return '';
        }

        return AmpError::$errors[$name];
    } // get

    /**
     * display
     * This prints the error out with a standard Error class span
     * Ben Goska: Renamed from print to display, print is reserved
     * @param string $name
     */
    public static function display($name)
    {
        // Be smart about this, if no error don't print
        if (isset(AmpError::$errors[$name])) {
            echo '<p class="alert alert-danger">' . T_(AmpError::$errors[$name]) . '</p>';
        }
    } // display

    /**
      * auto_init
     * This loads the errors from the session back into Ampache
     */
    public static function auto_init()
    {
        if (is_array($_SESSION['errors'])) {
            // Re-insert them
            foreach ($_SESSION['errors'] as $key => $error) {
                self::add($key, $error);
            }
        }
    } // auto_init
} // end amperror.class
