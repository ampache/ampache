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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Migration810028 (shipped in 8.2.0) narrowed `preference`.`type` to an enum of only four values, missing
 * `transcoding` - silently rewriting `encode_target`, `encode_video_target`, `encode_player_webplayer_target`
 * and `encode_player_api_target` to `string` and dropping their encode-format dropdown in the preferences UI
 * (`PreferenceRepository::getUserPreferenceRow()` only attaches it for `type` `special`/`transcoding`).
 */
final class Migration810030 extends AbstractMigration
{
    protected array $changelog = [
        'Widen `preference`.`type`\'s enum to admit `transcoding` again, restoring the encode-format dropdown on `encode_target`, `encode_video_target`, `encode_player_webplayer_target` and `encode_player_api_target` that 8.2.0 dropped',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        // widen first - the four rows below are still typed `string` under the narrower enum 8.2.0 shipped
        $this->updateDatabase("ALTER TABLE `preference` MODIFY COLUMN `type` enum('boolean','integer','string','special','transcoding') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("UPDATE `preference` SET `type` = 'transcoding' WHERE `name` IN ('encode_target', 'encode_video_target', 'encode_player_webplayer_target', 'encode_player_api_target');");
    }
}
