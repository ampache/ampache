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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add the per-user, per-format transcode bitrate overrides. A single preference holds a
 * `format=bps` map (e.g. `mp3=192000,opus=96000`) so the set of formats can follow the server's
 * configured `encode_args_<format>` keys without needing a migration whenever one is added.
 * An empty map means every format falls back to `transcode_bitrate`.
 */
final class Migration800019 extends AbstractMigration
{
    protected array $changelog = [
        'Add the per-user `transcode_bitrate_formats` preference holding per-format bitrate overrides',
    ];

    public function migrate(): void
    {
        $this->updatePreferences(
            'transcode_bitrate_formats',
            'Per-format transcode bitrate overrides in bps (falls back to Transcode Bitrate)',
            '',
            AccessLevelEnum::USER->value,
            'bitrate_map',
            'streaming',
            'transcoding'
        );
    }
}
