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

use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration700022 extends AbstractMigration
{
    protected array $changelog = ['Add user preferences to show/hide external search links on object pages.'];

    public function migrate(): void
    {
        $this->updatePreferences('external_links_google', 'Show Google search icon on library items', '1', 25, 'boolean', 'interface', 'library');
        $this->updatePreferences('external_links_duckduckgo', 'Show DuckDuckGo search icon on library items', '1', 25, 'boolean', 'interface', 'library');
        $this->updatePreferences('external_links_wikipedia', 'Show Wikipedia search icon on library items', '1', 25, 'boolean', 'interface', 'library');
        $this->updatePreferences('external_links_lastfm', 'Show Last.fm search on icon library items', '1', 25, 'boolean', 'interface', 'library');
        $this->updatePreferences('external_links_bandcamp', 'Show Bandcamp search on icon library items', '1', 25, 'boolean', 'interface', 'library');
        $this->updatePreferences('external_links_musicbrainz', 'Show MusicBrainz search icon on library items', '1', 25, 'boolean', 'interface', 'library');
    }
}
