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

final class Migration810006 extends AbstractMigration
{
    protected array $changelog = ['Group the branding settings together and add the icons a phone or a link preview asks for.'];

    public function migrate(): void
    {
        $this->updatePreferences(
            'custom_apple_touch_icon',
            'Custom URL - Touch icon for phone home screens (PNG, 180x180, no transparency)',
            '',
            AccessLevelEnum::ADMIN->value,
            'string',
            'system',
            'branding'
        );
        $this->updatePreferences(
            'custom_share_image',
            'Custom URL - Image shown when a link to this server is shared (PNG or JPEG, 1200x630)',
            '',
            AccessLevelEnum::ADMIN->value,
            'string',
            'system',
            'branding'
        );

        // the settings that dress an instance were split between the interface page and the system
        // one, sitting next to a timezone and a footer string. They now share a section of their own.
        $this->updateDatabase(
            "UPDATE `preference` SET `subcategory` = 'branding' WHERE `name` IN "
            . "('custom_favicon', 'custom_login_logo', 'custom_login_background');"
        );

        $this->updateDatabase(
            "UPDATE `preference` SET `description` = 'Custom URL - Favicon (SVG, PNG or ICO)' "
            . "WHERE `name` = 'custom_favicon';"
        );
    }
}
