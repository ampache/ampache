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

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Older releases created header-authenticated API sessions keyed by `MD5(username)`, a value anyone
 * could compute without ever holding a real credential. Those rows let a forged `Authorization` header
 * impersonate the user until the session expired, or forever under `perpetual_api_session`.
 */
final class Migration810019 extends AbstractMigration
{
    protected array $changelog = [
        'Remove predictable MD5(username) API sessions (GHSA-w28w-q7qp-8989)',
    ];

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `session` WHERE `type` = 'api' AND `id` = MD5(`username`);");
    }
}
