<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;

/**
 * ApiMessage
 *
 * Version aware wrappers around the per-version parameter checks and message output.
 *
 * `Api6::error()` and `Api::error()` (and their `check_parameter`, `check_access`, `empty` and
 * `message` siblings) are identical apart from the version of the data formatter they echo. That
 * single difference is what forces an otherwise identical api method to exist twice. These
 * wrappers take the api version resolved by the ApiHandler and dispatch to the matching class, so
 * a method whose parameters and checks do not change between versions can be shared.
 *
 * The methods echo their output and report whether the caller should keep going, exactly like the
 * functions they wrap - a `false` result means the error has already been written and the calling
 * api method must stop and return.
 *
 * Only the versions a shared method may run as are handled. Api3, Api4 and Api5 keep their own
 * (differently shaped) helpers.
 */
final class ApiMessage
{
    /**
     * Check the user has the required access level, echoing an error if not
     *
     * @param 6|8 $apiVersion
     * @return bool false if the access check failed and the error has been written
     */
    public static function checkAccess(
        int $apiVersion,
        AccessTypeEnum $type,
        AccessLevelEnum $level,
        int $userId,
        string $method,
        string $format = 'xml',
    ): bool {
        return match ($apiVersion) {
            6 => Api6::check_access($type, $level, $userId, $method, $format),
            8 => Api::check_access($type, $level, $userId, $method, $format),
        };
    }

    /**
     * Check the required parameters are present, echoing an error if not
     *
     * @param array<string, mixed> $input
     * @param string[] $parameters
     * @param 6|8 $apiVersion
     * @return bool false if a parameter is missing and the error has been written
     */
    public static function checkParameter(
        int $apiVersion,
        array $input,
        array $parameters,
        string $method,
    ): bool {
        return match ($apiVersion) {
            6 => Api6::check_parameter($input, $parameters, $method),
            8 => Api::check_parameter($input, $parameters, $method),
        };
    }

    /**
     * Echo an empty result for the requested object type
     *
     * @param 6|8 $apiVersion
     */
    public static function empty(
        int $apiVersion,
        ?string $emptyType,
        string $format = 'xml',
    ): void {
        match ($apiVersion) {
            6 => Api6::empty($emptyType, $format),
            8 => Api::empty($emptyType, $format),
        };
    }

    /**
     * Echo an error message
     *
     * @param 6|8 $apiVersion
     */
    public static function error(
        int $apiVersion,
        int|string $code,
        string $message,
        string $method,
        string $errorType,
        string $format = 'xml',
    ): void {
        match ($apiVersion) {
            6 => Api6::error($code, $message, $method, $errorType, $format),
            8 => Api::error($code, $message, $method, $errorType, $format),
        };
    }

    /**
     * Echo a success message
     *
     * @param array<string, string> $returnData
     * @param 6|8 $apiVersion
     */
    public static function message(
        int $apiVersion,
        string $message,
        string $format = 'xml',
        array $returnData = [],
    ): void {
        match ($apiVersion) {
            6 => Api6::message($message, $format, $returnData),
            8 => Api::message($message, $format, $returnData),
        };
    }

    /**
     * Resolve the api version the current request is running as.
     *
     * The ApiHandler puts the version it resolved into the input, so a legacy (static) api method
     * that never receives it as an argument can still reach it. Methods implementing
     * MethodInterface are handed the version directly and must not need this.
     *
     * @param array<string, mixed> $input
     * @return 6|8
     */
    public static function resolveVersion(array $input): int
    {
        $apiVersion = (int) ($input['api_version'] ?? Api::DEFAULT_VERSION);

        return ($apiVersion === 6)
            ? 6
            : 8;
    }
}
