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
 * Give the transcode bitrate a single override per player, matching the `encode_player_webplayer_target` and
 * `encode_player_api_target` output-format preferences that already exist. A rate of 0 means the player carries
 * no override of its own and takes the default `transcode_bitrate`.
 */
final class Migration800027 extends AbstractMigration
{
    protected array $changelog = [
        'Add the per-player `transcode_bitrate_webplayer` and `transcode_bitrate_api` bitrate overrides',
    ];

    public function migrate(): void
    {
        $level = AccessLevelEnum::USER->value;

        $this->updatePreferences('transcode_bitrate_webplayer', 'Transcode bitrate - Web Player (overrides default)', 0, $level, 'integer', 'streaming', 'transcoding');
        $this->updatePreferences('transcode_bitrate_api', 'Transcode bitrate - API (overrides default)', 0, $level, 'integer', 'streaming', 'transcoding');

        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` = 'transcode_bitrate_formats');");
        $this->updateDatabase("DELETE FROM `preference` WHERE `name` = 'transcode_bitrate_formats';");
    }
}
