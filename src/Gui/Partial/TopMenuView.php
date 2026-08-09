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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Session;
use Override;

/**
 * The icon bar across the top of the page.
 *
 * Ten near-identical blocks became one list, so an item is a row rather than six lines of markup.
 */
final class TopMenuView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $albumType,
        private readonly bool $fixed,
        private readonly bool $mayUse,
        private readonly bool $hasSession,
        private readonly bool $allowUpload,
    ) {}

    public function getContainerClass(): string
    {
        return 'topmenu_container-' . (($this->fixed) ? 'fixed' : 'float');
    }

    /**
     * @return list<array{url: string, icon: string, label: string, target: bool}>
     */
    public function getItems(): array
    {
        $items = [
            ['url' => '/index.php', 'icon' => 'topmenu-home', 'label' => T_('Home'), 'target' => false],
            ['url' => '/browse.php?action=album_artist', 'icon' => 'topmenu-artist', 'label' => T_('Artists'), 'target' => false],
            ['url' => '/mashup.php?action=' . $this->albumType, 'icon' => 'topmenu-album', 'label' => T_('Albums'), 'target' => false],
            ['url' => '/browse.php?action=playlist', 'icon' => 'topmenu-playlist', 'label' => T_('Playlists'), 'target' => false],
        ];

        if (!AmpConfig::get('sidebar_hide_search')) {
            $items[] = ['url' => '/browse.php?action=smartplaylist', 'icon' => 'topmenu-smartlist', 'label' => T_('Smartlists'), 'target' => false];
        }

        $items[] = ['url' => '/browse.php?action=tag&type=artist', 'icon' => 'topmenu-tagcloud', 'label' => T_('Genres'), 'target' => false];

        if (AmpConfig::get('live_stream')) {
            // the icon says "Radio Stations" while the label under it is the shorter "Radio"
            $items[] = ['url' => '/browse.php?action=live_stream', 'icon' => 'topmenu-radio', 'label' => T_('Radio'), 'target' => false];
        }

        if (AmpConfig::get('ratings') && $this->mayUse) {
            $items[] = ['url' => '/stats.php?action=userflag_' . $this->albumType, 'icon' => 'topmenu-favorite', 'label' => T_('Favorites'), 'target' => false];
        }

        if ($this->allowUpload) {
            $items[] = ['url' => '/upload.php', 'icon' => 'topmenu-upload', 'label' => T_('Upload'), 'target' => false];
        }

        if ($this->hasSession) {
            $items[] = ['url' => '/logout.php?session=' . Session::get(), 'icon' => 'topmenu-logout', 'label' => T_('Log out'), 'target' => true];
        }

        return $items;
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/top_menu.phtml');
    }
}
