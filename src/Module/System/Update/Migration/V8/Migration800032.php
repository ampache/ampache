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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Record what a client reported about its playback, for OpenSubsonic `NowPlayingEntry`
 *
 * `positionMs` is calculated from the last report, so it is stored rather than guessed from elapsed time.
 */
final class Migration800032 extends AbstractMigration
{
    /**
     * The reported columns, in the order they are appended.
     *
     * @var array<string, string>
     */
    private const array REPORT_COLUMNS = [
        'position_ms' => 'int(11) UNSIGNED DEFAULT NULL',
        'playback_rate' => 'float DEFAULT NULL',
        'state' => 'varchar(16) DEFAULT NULL',
    ];

    protected array $changelog = ['Add `position_ms`, `playback_rate` and `state` to `now_playing` for OpenSubsonic playback reports'];

    public function migrate(): void
    {
        foreach (self::REPORT_COLUMNS as $column => $definition) {
            // A partly-applied migration re-runs from the top, so a column is only added when it is absent.
            if (!Dba::has_column('now_playing', $column)) {
                $this->updateDatabase(
                    sprintf('ALTER TABLE `now_playing` ADD COLUMN `%s` %s;', $column, $definition)
                );
            }
        }
    }
}
