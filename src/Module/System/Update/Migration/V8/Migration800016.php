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
 * Move the transcode output-target settings from config-file keys to per-user preferences.
 * The existing config value (if any) seeds the default so current setups keep working.
 */
final class Migration800016 extends AbstractMigration
{
    protected array $changelog = [
        'Add per-user `encode_target`, `encode_video_target`, `encode_player_webplayer_target` and `encode_player_api_target` transcoding preferences',
    ];

    public function migrate(): void
    {
        $level = AccessLevelEnum::USER->value;

        $this->updatePreferences('encode_target', 'Default audio transcode output format', (string) AmpConfig::get('encode_target', ''), $level, 'transcoding', 'streaming', 'transcoding');
        $this->updatePreferences('encode_video_target', 'Default video transcode output format', (string) AmpConfig::get('encode_video_target', ''), $level, 'transcoding', 'streaming', 'transcoding');
        $this->updatePreferences('encode_player_webplayer_target', 'Web player transcode output format (overrides default)', (string) AmpConfig::get('encode_player_webplayer_target', ''), $level, 'transcoding', 'streaming', 'transcoding');
        $this->updatePreferences('encode_player_api_target', 'API transcode output format (overrides default)', (string) AmpConfig::get('encode_player_api_target', ''), $level, 'transcoding', 'streaming', 'transcoding');
    }
}
