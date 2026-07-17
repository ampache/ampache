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

namespace Ampache\Module\Api\Method\Exception;

use Ampache\Module\Api\Exception\ErrorCodeEnum;

/**
 * Raised when a user fails an access-level check.
 *
 * Mirrors the payload of the legacy Api::check_access() failure, which is distinct from
 * AccessDeniedException: a failed level check reports 4742/'account', a disabled feature 4703/'system'.
 */
final class AccessFailedException extends ApiMethodException
{
    /** @var int $code */
    protected $code = ErrorCodeEnum::FAILED_ACCESS_CHECK;

    protected string $type = 'account';
}
