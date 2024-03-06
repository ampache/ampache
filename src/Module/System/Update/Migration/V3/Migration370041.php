<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add Metadata tables and preferences
 */
final class Migration370041 extends AbstractMigration
{
    protected array $changelog = ['Add basic metadata tables'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `metadata_field` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) NOT NULL, `public` tinyint(1) NOT NULL, UNIQUE KEY `name` (`name`) ) ENGINE=$engine");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `metadata` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `object_id` INT(11) UNSIGNED NOT NULL, `field` INT(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) CHARACTER SET $charset DEFAULT NULL, KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`, `type`), KEY `objectfield` (`object_id`, `field`, `type`) ) ENGINE=$engine");

        $this->updatePreferences('disabled_custom_metadata_fields', 'Disable custom metadata fields (ctrl / shift click to select multiple)', '', AccessLevelEnum::ADMIN->value, 'string', 'system');
        $this->updatePreferences('disabled_custom_metadata_fields_input', 'Disable custom metadata fields. Insert them in a comma separated list. They will add to the fields selected above.', '', AccessLevelEnum::ADMIN->value, 'string', 'system');
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'metadata_field' => "CREATE TABLE IF NOT EXISTS `metadata_field` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) COLLATE $collation DEFAULT NULL, `public` tinyint(1) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        yield 'metadata' => "CREATE TABLE IF NOT EXISTS `metadata` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_id` int(11) UNSIGNED NOT NULL, `field` int(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`, `type`), KEY `objectfield` (`object_id`, `field`, `type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
