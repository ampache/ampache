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

namespace Ampache\Gui\Browse;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The row of dashboard category links above a mashup.
 */
final class DashboardFormView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $currentAction,
        private readonly bool $albumsAreGrouped,
        private readonly bool $maySeePlaylists,
        private readonly bool $podcastEnabled,
        private readonly bool $videoEnabled,
    ) {}

    /**
     * The album link follows the album_group preference, so its own tab stays current under either spelling.
     *
     * @return list<array{action: string, label: string, current: bool}>
     */
    public function getCategories(): array
    {
        $albumAction = $this->albumsAreGrouped ? 'album' : 'album_disk';

        $categories = [
            [
                'action' => $albumAction,
                'label' => T_('Albums'),
                'current' => in_array($this->currentAction, ['album', 'album_disk'], true),
            ],
            ['action' => 'artist', 'label' => T_('Artists'), 'current' => $this->currentAction === 'artist'],
        ];

        if ($this->maySeePlaylists) {
            $categories[] = ['action' => 'playlist', 'label' => T_('Playlists'), 'current' => $this->currentAction === 'playlist'];
        }

        if ($this->podcastEnabled) {
            $categories[] = ['action' => 'podcast_episode', 'label' => T_('Podcast Episodes'), 'current' => $this->currentAction === 'podcast_episode'];
        }

        if ($this->videoEnabled) {
            $categories[] = ['action' => 'video', 'label' => T_('Videos'), 'current' => $this->currentAction === 'video'];
        }

        return $categories;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('dashboard_form.phtml');
    }
}
