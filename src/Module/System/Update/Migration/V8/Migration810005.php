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

final class Migration810005 extends AbstractMigration
{
    protected array $changelog = ['Add preferences for drawn artwork on items that have no cover.'];

    public function migrate(): void
    {
        $this->updatePreferences('generated_art_enabled', 'Allow drawn artwork for items that have no cover', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'interface');
        $this->updatePreferences('generated_art_over_custom', 'Drawn artwork replaces the custom blank album image', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'interface');
        $this->updatePreferences('generated_art_template_lock', 'Force one drawn artwork template for every user', '', AccessLevelEnum::ADMIN->value, 'string', 'system', 'interface');
        $this->updatePreferences('generated_art', 'Draw artwork for items that have no cover', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'theme');
        $this->updatePreferences('generated_art_template', 'Drawn artwork template', 'auto', AccessLevelEnum::USER->value, 'string', 'interface', 'theme');
    }
}
