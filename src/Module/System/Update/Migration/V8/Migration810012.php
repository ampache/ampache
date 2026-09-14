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

final class Migration810012 extends AbstractMigration
{
    protected array $changelog = ['Add preferences for a private MusicBrainz server and the wait between its calls.'];

    public function migrate(): void
    {
        $this->updatePreferences('musicbrainz_server', 'MusicBrainz server URL, empty for musicbrainz.org', '', AccessLevelEnum::ADMIN->value, 'string', 'plugins', 'MusicBrainz');
        $this->updatePreferences('musicbrainz_throttle', 'Hundredths of a second to wait between MusicBrainz calls, musicbrainz.org requires at least 100', '100', AccessLevelEnum::ADMIN->value, 'integer', 'plugins', 'MusicBrainz');
    }
}
