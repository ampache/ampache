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
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add user_activity table
 */
final class Migration370040 extends AbstractMigration
{
    protected array $changelog = ['Add user_activity table'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_activity` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` INT(11) NOT NULL, `action` varchar(20) NOT NULL, `object_id` INT(11) UNSIGNED NOT NULL, `object_type` varchar(32) NOT NULL, `activity_date` INT(11) UNSIGNED NOT NULL) ENGINE=$engine;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 370040){
            yield 'user_activity' => "CREATE TABLE IF NOT EXISTS `user_activity` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` INT(11) NOT NULL, `action` varchar(20) NOT NULL, `object_id` INT(11) UNSIGNED NOT NULL, `object_type` varchar(32) NOT NULL, `activity_date` INT(11) UNSIGNED NOT NULL) ENGINE=$engine;";
        }
    }
}
