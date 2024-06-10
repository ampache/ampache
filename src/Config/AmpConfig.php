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

namespace Ampache\Config;

use Ampache\Module\System\AmpError;

/**
 * Config Class
 *
 * used to store static arrays of
 * config values, can read from ini files
 *
 * has static methods, this uses the global config
 * creating a 'Config' object will allow for local
 * config overrides and/or local configs (for like dba)
 * The class should be a static var in the other classes
 */
class AmpConfig
{
    /**
     * @var array $_global
     */
    private static $_global = [];

    /**
     * get
     *
     * This returns a config value.
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public static function get($name, $default = null)
    {
        if (isset(self::$_global[$name])) {
            return self::$_global[$name];
        }

        return $default;
    }

    /**
     * get_all
     *
     * This returns all of the current config variables as an array.
     * @return array
     */
    public static function get_all(): array
    {
        return self::$_global;
    }

    /**
     * get_rating_filter
     * Find out whether you are filtering ratings on your search
     * This function is used in mashup and random queries
     */
    public static function get_rating_filter(): int
    {
        $rating_filter = 0;
        if (self::get('rating_browse_filter')) {
            $rating_filter = (int)self::get('rating_browse_minimum_stars');
        }
        if ($rating_filter > 0 && $rating_filter <= 5) {
            return $rating_filter;
        }

        return 0;
    }

    /**
     * set
     *
     * This sets config values.
     * @param string $name
     * @param mixed $value
     * @param bool $clobber
     */
    public static function set($name, $value, $clobber = false): bool
    {
        if (isset(self::$_global[$name]) && !$clobber) {
            debug_event(self::class, "Tried to overwrite existing key $name without setting clobber", 5);
            AmpError::add('Config Global', sprintf(T_('Tried to clobber \'%s\' without setting clobber'), $name));

            return false;
        }
        self::$_global[$name] = $value;

        return true;
    }

    /**
     * set_by_array
     *
     * This is the same as the set function except it takes an array as
     * input.
     * @param array $array
     * @param bool $clobber
     */
    public static function set_by_array($array, $clobber = false): void
    {
        foreach ($array as $name => $value) {
            self::set($name, $value, $clobber);
        }

        // @todo refactor
        global $dic;
        $dic->get(ConfigContainerInterface::class)->updateConfig($array);
    }

    /**
     * get_skip_timer
     *
     * pull the timer and check using the time of the song for %complete skips
     * @param int $previous_time
     * @return int
     */
    public static function get_skip_timer($previous_time): ?int
    {
        $timekeeper = AmpConfig::get('skip_timer');
        $skip_time  = 20;
        if ((int)$timekeeper > 1) {
            $skip_time = $timekeeper;
        }
        if ($timekeeper < 1 && $timekeeper > 0) {
            $skip_time = (int)($previous_time * $timekeeper);
        }

        return $skip_time;
    }
}
