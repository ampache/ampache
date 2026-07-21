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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Move dynamic-downsampling `max_bit_rate`/`min_bit_rate` from config-file keys to per-user
 * preferences. Values are now stored in bits per second (bps); the previous config values were
 * kilobits, so they are multiplied by 1000 when seeding the default.
 */
final class Migration800017 extends AbstractMigration
{
    protected array $changelog = [
        'Add per-user `max_bit_rate`/`min_bit_rate` dynamic-downsampling preferences (stored in bps)',
    ];

    public function migrate(): void
    {
        $level = AccessLevelEnum::USER->value;

        // Previous config values were kilobits per second; store bps
        $max = (int) AmpConfig::get('max_bit_rate', 0);
        $min = (int) AmpConfig::get('min_bit_rate', 0);

        $this->updatePreferences('max_bit_rate', 'Maximum transcode bitrate for dynamic downsampling in bps (0 = disabled)', (string) ($max > 0 ? $max * 1000 : 0), $level, 'integer', 'streaming', 'transcoding');
        $this->updatePreferences('min_bit_rate', 'Minimum transcode bitrate for dynamic downsampling in bps', (string) ($min > 0 ? $min * 1000 : 8000), $level, 'integer', 'streaming', 'transcoding');
    }
}
