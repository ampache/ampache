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

/**
 * Add `total_skip` to podcast table
 */
final class Migration710006 extends AbstractMigration
{
    protected array $changelog = ['Add preferences to force Grid View on browse pages. (Set on login)'];

    protected bool $warning = true;

    public function migrate(): void
    {

        $this->updatePreferences('browse_song_grid_view', 'Force Grid View on Song browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_album_grid_view', 'Force Grid View on Album browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_album_disk_grid_view', 'Force Grid View on AlbumDisk browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_artist_grid_view', 'Force Grid View on Artist browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_live_stream_grid_view', 'Force Grid View on Radio Station browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_playlist_grid_view', 'Force Grid View on Playlist browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_video_grid_view', 'Force Grid View on Video browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_podcast_grid_view', 'Force Grid View on Podcast browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
        $this->updatePreferences('browse_podcast_episode_grid_view', 'Force Grid View on Podcast Episode browse', '0', AccessLevelEnum::USER->value, 'boolean', 'interface', 'cookies');
    }
}
