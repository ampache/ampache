<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Preference;

final class Migration700009 extends AbstractMigration
{
    protected array $changelog = ['Convert system preference `upload_catalog` into a user preference'];

    public function migrate(): void
    {
        $upload_catalog = (empty(Preference::get_by_user(-1, 'upload_catalog')))
            ? Preference::get_by_user(-1, 'upload_catalog')
            : -1;

        $this->updatePreferences('upload_catalog', 'Uploads catalog destination', '-1', AccessLevelEnum::ADMIN->value, 'integer', 'options', 'upload');
        $pref_id = Preference::id_from_name('upload_catalog');
        Preference::update_all($pref_id, $upload_catalog);
    }
}
