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

namespace Ampache\Gui\Index;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Browse\DashboardFormView;
use Ampache\Gui\Stats\RecentlyPlayedMode;
use Ampache\Gui\Stats\RecentlyPlayedView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\PluginDisplayHomeInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The home page: its header form, plugin widgets and the moment/recently-played panels.
 */
final class HomeView extends AbstractView
{
    public function __construct(
        private readonly ?User $user,
        private readonly string $browseForm,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly string $webPath,
        private readonly bool $mayUse,
    ) {}

    /**
     * The header is either the dashboard picker or the browse form the action built.
     */
    public function getHeaderForm(): string
    {
        if (!AmpConfig::get('index_dashboard_form')) {
            return $this->browseForm;
        }

        return new DashboardFormView(
            $this->webPath,
            (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS),
            (bool) AmpConfig::get('album_group'),
            $this->mayUse,
            (bool) AmpConfig::get('podcast'),
            (bool) AmpConfig::get('allow_video') && $this->videoRepository->getItemCount() > 0
        )->render();
    }

    /**
     * @return list<string>
     */
    public function getPluginWidgets(): array
    {
        if (!$this->user instanceof User) {
            return [];
        }

        $widgets = [];
        foreach (Plugin::get_plugins(PluginTypeEnum::HOMEPAGE_WIDGET) as $name) {
            $plugin = new Plugin($name);
            if ($plugin->_plugin instanceof PluginDisplayHomeInterface && $plugin->load($this->user)) {
                ob_start();
                $plugin->_plugin->display_home();
                $widgets[] = (string) ob_get_clean();
            }
        }

        return $widgets;
    }

    /**
     * The albums panel loads its own contents; which action it asks for depends on the grouping.
     */
    public function getRandomAlbumAction(): string
    {
        return (AmpConfig::get('album_group'))
            ? '?page=index&action=random_albums'
            : '?page=index&action=random_album_disks';
    }

    public function getRecentlyPlayed(): string
    {
        $userId   = $this->user?->getId() ?? -1;
        $allTypes = (bool) AmpConfig::get('home_recently_played_all');
        $data     = ($allTypes)
            ? Stats::get_recently_played($userId)
            : Stats::get_recently_played($userId, 'stream', 'song');
        if (!$allTypes) {
            Song::build_cache(array_keys($data));
        }

        return new RecentlyPlayedView(
            ($allTypes) ? RecentlyPlayedMode::ALL_TYPES : RecentlyPlayedMode::SONGS,
            $data,
            $this->user,
            $userId,
            false,
            $this->webPath
        )->render();
    }

    public function showMomentAlbums(): bool
    {
        return (bool) AmpConfig::get('home_moment_albums');
    }

    public function showMomentVideos(): bool
    {
        return (bool) AmpConfig::get('home_moment_videos') && (bool) AmpConfig::get('allow_video');
    }

    public function showNowPlaying(): bool
    {
        return (bool) AmpConfig::get('home_now_playing');
    }

    public function showRecentlyPlayed(): bool
    {
        return (bool) AmpConfig::get('home_recently_played');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('index/home.phtml');
    }
}
