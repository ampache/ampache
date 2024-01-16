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
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add system upload preferences
 * Add license information and user's artist association
 */
final class Migration370004 extends AbstractMigration
{
    protected array $changelog = ['Add license information and user\'s artist association'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updatePreferences('upload_catalog', 'Uploads catalog destination', '-1', 100, 'integer', 'system');
        $this->updatePreferences('allow_upload', 'Allow users to upload media', '0', 75, 'boolean', 'options');
        $this->updatePreferences('upload_subdir', 'Upload: create a subdirectory per user (recommended)', '1', 100, 'boolean', 'system');
        $this->updatePreferences('upload_user_artist', 'Upload: consider the user sender as the track\'s artist', '0', 100, 'boolean', 'system');
        $this->updatePreferences('upload_script', 'Upload: run the following script after upload (current directory = upload target directory)', '', 100, 'string', 'system');
        $this->updatePreferences('upload_allow_edit', 'Upload: allow users to edit uploaded songs', '1', 100, 'boolean', 'system');

        $sql_array = array(
            "ALTER TABLE `artist` ADD COLUMN `user` int(11) NULL AFTER `last_update`",
            "CREATE TABLE IF NOT EXISTS `license` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `description` varchar(256) NULL, `external_link` varchar(256) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('0 - default', '')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY', 'https://creativecommons.org/licenses/by/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC', 'https://creativecommons.org/licenses/by-nc/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC ND', 'https://creativecommons.org/licenses/by-nc-nd/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC SA', 'https://creativecommons.org/licenses/by-nc-sa/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY ND', 'https://creativecommons.org/licenses/by-nd/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY SA', 'https://creativecommons.org/licenses/by-sa/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Licence Art Libre', 'http://artlibre.org/licence/lal/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Yellow OpenMusic', 'http://openmusic.linuxtag.org/yellow.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Green OpenMusic', 'http://openmusic.linuxtag.org/green.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Gnu GPL Art', 'http://gnuart.org/english/gnugpl.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('WTFPL', 'https://en.wikipedia.org/wiki/WTFPL')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('FMPL', 'http://www.fmpl.org/fmpl.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('C Reaction', 'http://morne.free.fr/Necktar7/creaction.htm')",
            "ALTER TABLE `song` ADD COLUMN `user_upload` int(11) NULL AFTER `addition_time`, ADD COLUMN `license` int(11) NULL AFTER `user_upload`"
        );
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'license' => "CREATE TABLE IF NOT EXISTS `license` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `external_link` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=15 DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
