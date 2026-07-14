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

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Method\AbstractHandshakeMethod;
use Override;

/**
 * Verifies a new handshake and hands back a session token
 *
 * Api version 6 is deliberately lax about the version it will shake hands with, so as not to break
 * client compatibility: anything at or above the auth version is served, and a version 6 client is
 * always served. Version 7 was never released, so it is folded onto 6.
 */
final class Handshake6Method extends AbstractHandshakeMethod
{
    #[Override]
    protected function isSupportedVersion(string $version, int $dataVersion): bool
    {
        // only check against the initial version to not break client compatibility
        return (int) $version >= Api6::$auth_version
            || $dataVersion === 6;
    }

    #[Override]
    protected function normalizeDataVersion(int $dataVersion): int
    {
        // version 7 was skipped; treat those clients as version 6
        return ($dataVersion === 7) ? 6 : $dataVersion;
    }
}
