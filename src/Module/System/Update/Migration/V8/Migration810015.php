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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add the `jellyfin_quick_connect` table and the `quickconnect_enable` preference
 *
 * Off by default and independent of `jellyfin_backend_enable`, since it is the only part of the Jellyfin
 * surface that mints credentials on its own rather than checking a password. The preference isn't
 * Jellyfin-specific: it also gates QuickConnect pairing for the native API, so it is named for the
 * feature rather than the first protocol that used it. This replaces an unreleased Migration810014 that
 * shipped under the old `jellyfin_quickconnect_enable` name; the delete below cleans that name up for
 * anyone who already ran it from `develop`, and is a no-op on a fresh install that never had it.
 */
final class Migration810015 extends AbstractMigration
{
    private const string QUICK_CONNECT_TABLE = "CREATE TABLE IF NOT EXISTS `jellyfin_quick_connect` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `secret` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL, `code` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL, `device_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `device_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `app_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `app_version` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `user_id` int(11) DEFAULT NULL, `authorized` tinyint(1) NOT NULL DEFAULT 0, `date_added` int(11) UNSIGNED NOT NULL, `expires` int(11) UNSIGNED NOT NULL, `consumed` tinyint(1) NOT NULL DEFAULT 0, `authorize_attempts` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `secret` (`secret`), UNIQUE KEY `code` (`code`), KEY `expires` (`expires`), KEY `device_id` (`device_id`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;";

    protected array $changelog = [
        'Add `jellyfin_quick_connect` table',
        'Add `quickconnect_enable` preference to enable/disable QuickConnect pairing',
    ];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 810015) {
            yield 'jellyfin_quick_connect' => sprintf(self::QUICK_CONNECT_TABLE, $engine, $charset, $collation);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase(sprintf(self::QUICK_CONNECT_TABLE, $engine, $charset, $collation));

        // cleans up the old name for anyone who ran the unreleased Migration810014; a no-op otherwise
        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` = 'jellyfin_quickconnect_enable');");
        $this->updateDatabase("DELETE FROM `preference` WHERE `name` = 'jellyfin_quickconnect_enable';");

        $this->updatePreferences(
            'quickconnect_enable',
            'Enable QuickConnect service',
            '0',
            AccessLevelEnum::ADMIN->value,
            'boolean',
            'system',
            'backend',
        );
    }
}
