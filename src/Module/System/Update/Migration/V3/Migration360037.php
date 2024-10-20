<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add sharing features
 */
final class Migration360037 extends AbstractMigration
{
    protected array $changelog = ['Add sharing features'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `share` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `allow_stream` tinyint(1) unsigned NOT NULL DEFAULT '0', `allow_download` tinyint(1) unsigned NOT NULL DEFAULT '0', `expire_days` int(4) unsigned NOT NULL DEFAULT '0', `max_counter` int(4) unsigned NOT NULL DEFAULT '0', `secret` varchar(20) CHARACTER SET $charset NULL, `counter` int(4) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NOT NULL DEFAULT '0', `lastvisit_date` int(11) unsigned NOT NULL DEFAULT '0', `public_url` varchar(255) CHARACTER SET $charset NULL, `description` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine;");

        $this->updatePreferences('share', 'Allow Share', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'options');
        $this->updatePreferences('share_expire', 'Share links default expiration days (0=never)', '7', AccessLevelEnum::ADMIN->value, 'integer', 'system');
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360037) {
            yield 'share' => "CREATE TABLE IF NOT EXISTS `share` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `allow_stream` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `allow_download` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `expire_days` int(4) UNSIGNED NOT NULL DEFAULT 0, `max_counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `secret` varchar(20) COLLATE $collation DEFAULT NULL, `counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastvisit_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `public_url` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(255) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
