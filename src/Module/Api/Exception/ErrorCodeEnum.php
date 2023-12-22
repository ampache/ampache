<?php

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

namespace Ampache\Module\Api\Exception;

final class ErrorCodeEnum
{
    public const ACCESS_CONTROL_NOT_ENABLED = 4700;
    public const INVALID_HANDSHAKE          = 4701;
    public const GENERIC_ERROR              = 4702;
    public const ACCESS_DENIED              = 4703;
    public const NOT_FOUND                  = 4704;
    public const MISSING                    = 4705;
    public const DEPRECATED                 = 4706;
    public const BAD_REQUEST                = 4710;
    public const FAILED_ACCESS_CHECK        = 4742;
}
