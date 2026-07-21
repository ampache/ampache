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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Convert the existing `transcode_bitrate` preference from kilobits to bits per second (bps).
 * Existing per-user values in the kbps range are multiplied by 1000; the guard avoids
 * double-converting values that are already stored as bps.
 */
final class Migration800018 extends AbstractMigration
{
    protected array $changelog = [
        'Store `transcode_bitrate` in bits per second (bps) and migrate existing kilobit values',
    ];

    public function migrate(): void
    {
        // kilobit values (roughly 1-2000) become bps; values already in the bps range are left alone
        $this->updateDatabase("UPDATE `user_preference` SET `value` = CAST(`value` AS UNSIGNED) * 1000 WHERE `name` = 'transcode_bitrate' AND CAST(`value` AS UNSIGNED) BETWEEN 1 AND 2000;");
        $this->updateDatabase("UPDATE `preference` SET `value` = '128000', `type` = 'integer', `description` = 'Transcode Bitrate (bps)' WHERE `name` = 'transcode_bitrate';");
    }
}
