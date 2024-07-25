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
 * Add User messages and user follow tables.
 */
final class Migration370034 extends AbstractMigration
{
    protected array $changelog = ['Add User messages and user follow tables'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_pvmsg` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `subject` varchar(80) NOT NULL, `message` TEXT CHARACTER SET $charset NULL, `from_user` int(11) unsigned NOT NULL, `to_user` int(11) unsigned NOT NULL, `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_follower` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `follow_user` int(11) unsigned NOT NULL, `follow_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine");

        $this->updatePreferences('notify_email', 'Receive notifications by email (shouts, private messages, ...)', '0', AccessLevelEnum::USER->value, 'boolean', 'options');
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 370034) {
            yield 'user_pvmsg' => "CREATE TABLE IF NOT EXISTS `user_pvmsg` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `subject` varchar(80) COLLATE $collation DEFAULT NULL, `message` text COLLATE $collation DEFAULT NULL, `from_user` int(11) UNSIGNED NOT NULL, `to_user` int(11) UNSIGNED NOT NULL, `is_read` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'user_follower' => "CREATE TABLE IF NOT EXISTS `user_follower` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `follow_user` int(11) UNSIGNED NOT NULL, `follow_date` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
