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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add share_access_level, who gets share and share_text (the sentence posted with the link)
 */
final class Migration850003 extends AbstractMigration
{
    protected array $changelog = [
        'Add `share_access_level` to choose who gets the share line, guests included',
        'Add `share_text`, the sentence posted with a shared link',
    ];

    public function migrate(): void
    {
        $this->updatePreferences('share_access_level', 'Share Access Level', AccessLevelEnum::USER->value, AccessLevelEnum::ADMIN->value, 'special', 'system', 'share');
        $this->updatePreferences('share_text', 'Share text posted with the link', '', AccessLevelEnum::ADMIN->value, 'string', 'system', 'share');
    }
}
