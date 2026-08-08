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

namespace Ampache\Gui\Sidebar;

use Ampache\Config\AmpConfig;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The home sidebar tab: browse, dashboard, playlist, search and information.
 *
 * Each section carries a CSS order the user can set, so they can be rearranged without markup changes.
 */
final class HomeSidebarView extends AbstractSidebarView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $albumType,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly FolderRepositoryInterface $folderRepository,
        private readonly bool $mayUse,
        private readonly bool $mayManage,
        private readonly bool $allowUpload,
    ) {}

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getBrowseItems(): array
    {
        $items = [];
        if (AmpConfig::get('show_folder') && $this->folderRepository->getItemCount()) {
            $items[] = ['id' => 'sb_home_browse_folder', 'url' => '/folders.php?action=show&folder=-1', 'label' => T_('Folders')];
        }

        $items[] = ['id' => 'sb_home_browse_songTitle', 'url' => '/browse.php?action=song', 'label' => T_('Songs')];
        $items[] = ['id' => 'sb_home_browse_album', 'url' => '/browse.php?action=' . $this->albumType, 'label' => T_('Albums')];

        if (AmpConfig::get('show_artist')) {
            $items[] = ['id' => 'sb_home_browse_artist', 'url' => '/browse.php?action=artist', 'label' => T_('Artists')];
        }

        // the album-artist entry stands in for the artist one when that is hidden
        if (AmpConfig::get('show_album_artist') || !AmpConfig::get('show_artist')) {
            $items[] = ['id' => 'sb_home_browse_album_artist', 'url' => '/browse.php?action=album_artist', 'label' => T_('Album Artists')];
        }

        if (AmpConfig::get('label')) {
            $items[] = ['id' => 'sb_home_browse_label', 'url' => '/browse.php?action=label', 'label' => T_('Labels')];
        }

        if (AmpConfig::get('broadcast')) {
            $items[] = ['id' => 'sb_home_browse_broadcast', 'url' => '/browse.php?action=broadcast', 'label' => T_('Broadcasts')];
        }

        if (AmpConfig::get('live_stream')) {
            $items[] = ['id' => 'sb_home_browse_radioStation', 'url' => '/browse.php?action=live_stream', 'label' => T_('Radio Stations')];
        }

        if (AmpConfig::get('podcast')) {
            $items[] = ['id' => 'sb_home_browse_podcast', 'url' => '/browse.php?action=podcast', 'label' => T_('Podcasts')];
            $items[] = ['id' => 'sb_home_browse_podcast_episode', 'url' => '/browse.php?action=podcast_episode', 'label' => T_('Podcast Episodes')];
        }

        if ($this->allowVideo()) {
            $items[] = ['id' => 'sb_home_browse_video', 'url' => '/browse.php?action=video', 'label' => T_('Videos')];
        }

        $items[] = ['id' => 'sb_home_browse_tags', 'url' => '/browse.php?action=tag&type=' . $this->albumType, 'label' => T_('Genres')];

        if ($this->allowUpload) {
            $items[] = ['id' => 'sb_home_browse_upload', 'url' => '/stats.php?action=upload', 'label' => T_('Uploads')];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getDashboardItems(): array
    {
        $items = [
            ['id' => 'sb_home_dashboard_albums', 'url' => '/mashup.php?action=' . $this->albumType, 'label' => T_('Albums')],
            ['id' => 'sb_home_dashboard_artists', 'url' => '/mashup.php?action=artist', 'label' => T_('Artists')],
        ];

        if ($this->mayUse) {
            $items[] = ['id' => 'sb_home_dashboard_playlists', 'url' => '/mashup.php?action=playlist', 'label' => T_('Playlists')];
        }

        if (AmpConfig::get('podcast')) {
            $items[] = ['id' => 'sb_home_dashboard_podcast_episodes', 'url' => '/mashup.php?action=podcast_episode', 'label' => T_('Podcast Episodes')];
        }

        if ($this->allowVideo() && !AmpConfig::get('sidebar_hide_video')) {
            $items[] = ['id' => 'sb_home_dashboard_videos', 'url' => '/mashup.php?action=video', 'label' => T_('Videos')];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getInformationItems(): array
    {
        $items = [
            ['id' => 'sb_home_information_recent', 'url' => '/stats.php?action=recent_' . $this->albumType, 'label' => T_('Recent')],
            ['id' => 'sb_home_information_newest', 'url' => '/stats.php?action=newest_' . $this->albumType, 'label' => T_('Newest')],
            ['id' => 'sb_home_information_popular', 'url' => '/stats.php?action=popular_' . $this->albumType, 'label' => T_('Popular')],
        ];

        if (User::is_registered()) {
            if (AmpConfig::get('ratings')) {
                $items[] = ['id' => 'sb_home_information_highest', 'url' => '/stats.php?action=highest_' . $this->albumType, 'label' => T_('Top Rated')];
                $items[] = ['id' => 'sb_home_information_userFlag', 'url' => '/stats.php?action=userflag_' . $this->albumType, 'label' => T_('Favorites')];
            }

            if (AmpConfig::get('wanted')) {
                $items[] = ['id' => 'sb_home_information_wanted', 'url' => '/stats.php?action=wanted', 'label' => T_('Wanted')];
            }

            if (AmpConfig::get('share')) {
                $items[] = ['id' => 'sb_home_information_share', 'url' => '/stats.php?action=share', 'label' => T_('Shares')];
            }
        }

        $items[] = ['id' => 'sb_home_information_statistic', 'url' => '/stats.php?action=show', 'label' => T_('Statistics')];

        return $items;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getPlaylistItems(): array
    {
        $items = [
            ['id' => 'sb_home_playlist_playlist', 'url' => '/browse.php?action=playlist', 'label' => T_('Playlists')],
            ['id' => 'sb_home_playlist_smartPlaylist', 'url' => '/browse.php?action=smartplaylist', 'label' => T_('Smart Playlists')],
        ];

        if (AmpConfig::get('show_collection')) {
            $items[] = ['id' => 'sb_home_playlist_collection', 'url' => '/browse.php?action=collection', 'label' => T_('Collections')];
        }

        if (AmpConfig::get('allow_democratic_playback')) {
            $items[] = ['id' => 'sb_home_playlist_democratic', 'url' => '/democratic.php?action=show_playlist', 'label' => T_('Democratic')];
        }

        if (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller')) {
            $items[] = ['id' => 'sb_home_playlist_show', 'url' => '/localplay.php?action=show_playlist', 'label' => T_('Localplay')];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getSearchItems(): array
    {
        $items = [
            ['id' => 'sb_home_search_song', 'url' => '/search.php?type=song', 'label' => T_('Songs')],
            ['id' => 'sb_home_search_album', 'url' => '/search.php?type=' . $this->albumType, 'label' => T_('Albums')],
            ['id' => 'sb_home_search_artist', 'url' => '/search.php?type=artist', 'label' => T_('Artists')],
        ];

        if (AmpConfig::get('label')) {
            $items[] = ['id' => 'sb_home_search_label', 'url' => '/search.php?type=label', 'label' => T_('Labels')];
        }

        if ($this->mayUse) {
            $items[] = ['id' => 'sb_home_search_playlist', 'url' => '/search.php?type=playlist', 'label' => T_('Playlists')];
        }

        if (AmpConfig::get('podcast')) {
            $items[] = ['id' => 'sb_home_search_podcast', 'url' => '/search.php?type=podcast', 'label' => T_('Podcasts')];
            $items[] = ['id' => 'sb_home_search_podcast_episode', 'url' => '/search.php?type=podcast_episode', 'label' => T_('Podcast Episodes')];
        }

        if ($this->allowVideo()) {
            $items[] = ['id' => 'sb_home_search_video', 'url' => '/search.php?type=video', 'label' => T_('Videos')];
        }

        $items[] = ['id' => 'sb_home_random_advanced', 'url' => '/random.php?action=advanced&type=song', 'label' => T_('Random')];

        return $items;
    }

    /**
     * The sections in the order they appear, each with the css order the user configured.
     *
     * @return list<array{key: string, title: string, class: string, order: int, items: list<array{id: string, url: string, label: string}>}>
     */
    public function getSections(): array
    {
        $sections = [];
        if (!AmpConfig::get('sidebar_hide_browse')) {
            $sections[] = ['key' => 'home_browse', 'title' => T_('Browse'), 'class' => 'sb2_browse', 'order' => $this->getOrder('browse', 10), 'items' => $this->getBrowseItems()];
        }

        if (User::is_registered() && !AmpConfig::get('sidebar_hide_dashboard')) {
            $sections[] = ['key' => 'home_dashboard', 'title' => T_('Dashboards'), 'class' => 'sb2_dashboard', 'order' => $this->getOrder('dashboard', 15), 'items' => $this->getDashboardItems()];
        }

        if (AmpConfig::get('home_now_playing') || AmpConfig::get('allow_democratic_playback') || $this->mayManage) {
            $sections[] = ['key' => 'home_playlist', 'title' => T_('Playlists'), 'class' => 'sb2_playlist', 'order' => $this->getOrder('playlist', 30), 'items' => $this->getPlaylistItems()];
        }

        if (!AmpConfig::get('sidebar_hide_search')) {
            $sections[] = ['key' => 'home_search', 'title' => T_('Search'), 'class' => 'sb2_search', 'order' => $this->getOrder('search', 40), 'items' => $this->getSearchItems()];
        }

        if (!AmpConfig::get('sidebar_hide_information')) {
            $sections[] = ['key' => 'home_information', 'title' => T_('Information'), 'class' => 'sb2_information', 'order' => $this->getOrder('information', 60), 'items' => $this->getInformationItems()];
        }

        return $sections;
    }

    public function getSectionState(string $key): string
    {
        return ($this->isSectionCollapsed($key)) ? 'collapsed' : 'expanded';
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    /**
     * The dashboard and information sections start closed; the rest start open.
     */
    public function isSectionCollapsed(string $key): bool
    {
        $default = in_array($key, ['home_dashboard', 'home_information'], true) ? 'collapsed' : 'expanded';

        return (($_COOKIE['sb_' . $key] ?? $default) !== 'expanded');
    }

    public function showBrowseFilter(): bool
    {
        return (bool) AmpConfig::get('browse_filter');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('sidebar/home.phtml');
    }

    private function allowVideo(): bool
    {
        return (bool) AmpConfig::get('allow_video') && $this->videoRepository->getItemCount() > 0;
    }

    private function getOrder(string $name, int $default): int
    {
        return (int) AmpConfig::get('sidebar_order_' . $name, $default) ?: $default;
    }
}
