<?php

declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Module\Util;

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\playable_item;

/**
 * Provides utility methods to check whether an object implements a certain interface
 */
final class InterfaceImplementationChecker
{
    /**
     * Checks if an object implements a certain interface
     *
     * @param string $instance The subject to search in
     * @param string $interface_name The interface name to search for
     */
    private static function is_class_typeof(string $instance, string $interface_name): bool
    {
        if (empty($instance)) {
            return false;
        }
        $instance = ObjectTypeToClassNameMapper::map($instance);
        if (class_exists($instance)) {
            return in_array(
                $interface_name,
                array_map(
                    static function (string $name): string {
                        return $name;
                    },
                    class_implements($instance)
                )
            );
        }

        return false;
    }

    /**
     * @param string $instance The subject to search in
     */
    public static function is_playable_item(string $instance): bool
    {
        return self::is_class_typeof($instance, playable_item::class);
    }

    /**
     * @param string $instance The subject to search in
     */
    public static function is_library_item(string $instance): bool
    {
        return self::is_class_typeof($instance, library_item::class);
    }

    /**
     * @param string $instance The subject to search in
     */
    public static function is_media(string $instance): bool
    {
        return self::is_class_typeof($instance, Media::class);
    }
}
