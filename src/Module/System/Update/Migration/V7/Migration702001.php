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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration702001 extends AbstractMigration
{
    protected array $changelog = [
        'Update cCreative Commons 3.0 licenses with a version suffix',
        'Add Creative Commons 4.0 licenses if their `external_link` doesn\'t exist',
    ];

    public function migrate(): void
    {
        // add suffix for 3.0 licenses
        $this->updateDatabase("UPDATE `license` SET `name` = CONCAT(`name`, ' 3.0') WHERE `external_link` LIKE '%://creativecommons.org/licenses/%/3.0/' AND `name` NOT LIKE '% 3.0';");

        // get order and add from there
        $order   = Dba::read("SELECT MAX(`order`) AS `order` FROM license;", [], true);
        $results = Dba::fetch_assoc($order);
        $count   = ($results['order'] ?? 0) + 1;

        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY', NULL, 'https://creativecommons.org/licenses/by/4.0/', ?);", [$count]);
            $count++;
        }
        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by-nc/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY NC', NULL, 'https://creativecommons.org/licenses/by-nc/4.0/' ,?);", [$count]);
            $count++;
        }
        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by-nc-nd/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY NC ND', NULL, 'https://creativecommons.org/licenses/by-nc-nd/4.0/', ?);", [$count]);
            $count++;
        }
        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by-nc-sa/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY NC SA', NULL, 'https://creativecommons.org/licenses/by-nc-sa/4.0/', ?);", [$count]);
            $count++;
        }
        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by-nd/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY ND', NULL, 'https://creativecommons.org/licenses/by-nd/4.0/', ?);", [$count]);
            $count++;
        }
        if (!Dba::read('SELECT `external_link` FROM `license` WHERE external_link = ?;', ['https://creativecommons.org/licenses/by-sa/4.0/'], true)) {
            $this->updateDatabase("INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES ('CC BY SA', NULL, 'https://creativecommons.org/licenses/by-sa/4.0/', ?);", [$count]);
        }
    }
}
