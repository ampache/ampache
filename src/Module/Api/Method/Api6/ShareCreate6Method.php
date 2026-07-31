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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Method\AbstractShareCreateMethod;
use Override;

/**
 * Creates a share for an object
 *
 * Api version 6 reports the resolved type back in the type error, so a zero-id playlist is reported
 * as `search` rather than as the `playlist` that was asked for.
 */
final class ShareCreate6Method extends AbstractShareCreateMethod
{
    /**
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function reportedType(array $input, string $objectType): string
    {
        return $objectType;
    }
}
