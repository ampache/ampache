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
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add a `subsonic_secret` column to the user table so Subsonic clients can use real token authentication. A Subsonic
 * client sends `t = md5(secret . salt)`, which the server can only verify when it is able to recover the secret, and
 * the sha256 login password is one-way; the api key was substituted into that formula instead, forcing users to paste
 * it into a music player. This column holds a dedicated, reversibly-encrypted Subsonic password of the user's choosing.
 *
 * The column is checked before it is added rather than dropped and recreated, because a re-run after a partial failure
 * would otherwise discard every secret that had already been set on a live instance.
 *
 * No Ampache7 rollback block is needed: Ampache7 never reads the column, so a downgraded database keeps an unused
 * nullable column rather than losing anything, and dropping it would throw away the secrets on a later re-upgrade.
 */
final class Migration800025 extends AbstractMigration
{
    protected array $changelog = [
        'Add `subsonic_secret` to the `user` table to allow Subsonic token authentication without the api key',
    ];

    public function migrate(): void
    {
        if ($this->hasColumn()) {
            return;
        }

        $this->updateDatabase("ALTER TABLE `user` ADD COLUMN `subsonic_secret` varchar(255) DEFAULT NULL;");
    }

    private function hasColumn(): bool
    {
        $db_results = Dba::read(
            'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = \'user\' AND `COLUMN_NAME` = \'subsonic_secret\';'
        );

        return Dba::fetch_assoc($db_results) !== [];
    }
}
