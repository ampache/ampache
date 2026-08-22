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
use Ampache\Gui\Sidebar\SidebarViewFactoryInterface;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Override;

/**
 * Everything above the page body: the document head, the header bar, both sidebars and the rightbar.
 *
 * The markup it returns is deliberately unbalanced -- it opens `#maincontainer` and `#guts`, which
 * `FooterView` closes, so the two are a matched pair and neither renders a whole document alone.
 */
final class HeaderView extends AbstractView
{
    /**
     * Pages showing one library item: the script name, then each type it can show with the id parameters
     * it may be called with. The first match wins, so a more specific type comes first.
     */
    private const array OBJECT_PAGES = [
        'albums' => [
            ['album_disk', ['album_disk']],
            ['album', ['album']],
        ],
        'artists' => [['artist', ['artist']]],
        'song' => [['song', ['song_id']]],
        'playlist' => [['playlist', ['playlist_id']]],
        'smartplaylist' => [['search', ['playlist_id']]],
        'podcast' => [['podcast', ['podcast']]],
        'podcast_episode' => [['podcast_episode', ['podcast_episode', 'podcast']]],
        'video' => [['video', ['video_id', 'video']]],
        'radio' => [['live_stream', ['radio', 'live_stream']]],
        'labels' => [['label', ['label']]],
    ];

    /**
     * An icon per object type, so a tab is recognisable when its title is cut short.
     */
    private const array TYPE_ICONS = [
        'album' => "\u{1F4BF}",
        'album_disk' => "\u{1F4BF}",
        'artist' => "\u{1F3A4}",
        'song' => "\u{1F3B5}",
        'playlist' => "\u{1F4C3}",
        'search' => "\u{26A1}",
        'tag' => "\u{1F3BC}",
        'podcast' => "\u{1F399}\u{FE0F}",
        'podcast_episode' => "\u{1F399}\u{FE0F}",
        'video' => "\u{1F3AC}",
        'live_stream' => "\u{1F4FB}",
        'label' => "\u{1F3E2}",
    ];

    public function __construct(
        private readonly string $webPath,
        private readonly string $adminPath,
        private readonly EnvironmentInterface $environment,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly PlaylistLoaderInterface $playlistLoader,
        private readonly PrivateMessageRepositoryInterface $privateMessageRepository,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly SidebarViewFactoryInterface $sidebarViewFactory,
        private readonly ?User $currentUser,
        private readonly string $sidebarTab,
        private readonly bool $isAdmin,
        private readonly bool $mayUse,
        private readonly bool $allowUpload,
        private readonly bool $isSession,
    ) {}

    public function allowsUpload(): bool
    {
        return $this->allowUpload;
    }

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    public function getAjaxUriRetriever(): AjaxUriRetrieverInterface
    {
        return $this->ajaxUriRetriever;
    }

    public function getAlbumType(): string
    {
        return (AmpConfig::get('album_group')) ? 'album' : 'album_disk';
    }

    public function getContentClass(): string
    {
        $class = 'content-' . (($this->isUiFixed())
            ? ((AmpConfig::get('topmenu')) ? 'fixed-topmenu' : 'fixed')
            : 'float');

        if ($this->hasTempPlaylist() && AmpConfig::get('play_type') !== 'localplay') {
            $class .= ' content-right-wild';
        }

        return $class . (($this->isSidebarCollapsed()) ? ' content-left-wild' : '');
    }

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', $this->getSiteLanguage());
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    public function getLogoUrl(): string
    {
        return ($this->currentUser instanceof User && $this->currentUser->getId() > 0 && $this->hasCustomLogo())
            ? $this->currentUser->get_avatar()['url_medium'] ?? Ui::get_logo_url()
            : Ui::get_logo_url();
    }

    /**
     * The browser tab title: what the page shows, then the site title, so several open tabs stay apart.
     */
    public function getPageTitle(): string
    {
        $site    = $this->getSiteTitle();
        $script  = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
        $context = $this->getPageContext($script);

        return ($context === null)
            ? $site
            : $context . ' - ' . $site;
    }

    /**
     * The rss alternates the head advertises, empty when the feature is off.
     *
     * @return list<array{type: string, title: string}>
     */
    public function getRssFeeds(): array
    {
        if (!AmpConfig::get('use_rss')) {
            return [];
        }

        $feeds = [
            ['type' => RssFeedTypeEnum::NOW_PLAYING->value, 'title' => T_('Now Playing')],
            ['type' => RssFeedTypeEnum::RECENTLY_PLAYED->value, 'title' => T_('Recently Played')],
            ['type' => RssFeedTypeEnum::LATEST_ALBUM->value, 'title' => T_('Newest Albums')],
            ['type' => RssFeedTypeEnum::LATEST_ARTIST->value, 'title' => T_('Newest Artists')],
            ['type' => RssFeedTypeEnum::LATEST_SONG->value, 'title' => T_('Newest Songs')],
        ];

        if ($this->isSociable()) {
            $feeds[] = ['type' => RssFeedTypeEnum::LATEST_SHOUT->value, 'title' => T_('Newest Shouts')];
        }

        return $feeds;
    }

    public function getSiteCharset(): string
    {
        return (string) AmpConfig::get('site_charset', 'UTF-8');
    }

    public function getSiteLanguage(): string
    {
        return (string) AmpConfig::get('lang', 'en_US');
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title');
    }

    public function getTextDirection(): string
    {
        return (is_rtl($this->getSiteLanguage())) ? 'rtl' : 'ltr';
    }

    public function getUnreadMessageCount(): int
    {
        return ($this->currentUser instanceof User)
            ? $this->privateMessageRepository->getUnreadCount($this->currentUser)
            : 0;
    }

    public function getUserFullname(): string
    {
        return $this->currentUser->fullname ?? '';
    }

    public function getUserId(): int
    {
        return $this->currentUser?->getId() ?? 0;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    /**
     * Asked as a yes/no so the header never counts a play queue it is not going to render.
     */
    public function hasTempPlaylist(): bool
    {
        return $this->currentUser instanceof User
            && !empty($this->currentUser->playlist)
            && $this->currentUser->playlist->has_items();
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isSession(): bool
    {
        return $this->isSession;
    }

    /**
     * The sidebar starts collapsed when the light variant is on, or when the cookie says so.
     */
    public function isSidebarCollapsed(): bool
    {
        $light = (bool) AmpConfig::get('sidebar_light');
        $state = $_COOKIE['sidebar_state'] ?? null;

        return ($light && ($this->isSwitcherHidden() || $state !== 'expanded'))
            || $state === 'collapsed';
    }

    public function isSociable(): bool
    {
        return (bool) AmpConfig::get('sociable');
    }

    public function isSwitcherHidden(): bool
    {
        return (bool) AmpConfig::get('sidebar_hide_switcher', false);
    }

    public function isUiFixed(): bool
    {
        return (bool) AmpConfig::get('ui_fixed');
    }

    public function mayUse(): bool
    {
        return $this->mayUse;
    }

    public function renderRightbar(): string
    {
        return new RightbarView(
            $this->collectionRepository,
            $this->libraryItemLoader,
            $this->playlistLoader,
            $this->zipHandler,
            $this->webPath
        )->render();
    }

    public function renderSidebar(): string
    {
        return $this->sidebarViewFactory->createSidebarView($this->sidebarTab)->render();
    }

    public function showAmpacheMessage(): bool
    {
        return !AmpConfig::get('hide_ampache_messages', false);
    }

    public function showConfigOutOfDate(): bool
    {
        return AmpConfig::get_int('int_config_version') > AmpConfig::get_int('config_version');
    }

    public function showHeaderLogin(): bool
    {
        return (bool) AmpConfig::get('show_header_login');
    }

    public function showNewVersion(): bool
    {
        if (!AmpConfig::get('autoupdate')) {
            return false;
        }

        $latest = AutoUpdate::get_latest_version();

        return (!empty($latest) && AutoUpdate::get_current_version() !== $latest) || AutoUpdate::is_update_available();
    }

    public function showPluginUpdate(): bool
    {
        return Plugin::is_update_available();
    }

    public function showRegisterLink(): bool
    {
        return (bool) AmpConfig::get('allow_public_registration')
            && (Mailer::is_mail_enabled() || (bool) AmpConfig::get('user_no_email_confirm', false));
    }

    public function showTopMenu(): bool
    {
        return (bool) AmpConfig::get('topmenu');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/header.phtml');
    }

    /**
     * The leading part of the tab title, or null for a page that has nothing of its own to say.
     */
    private function getPageContext(string $script): ?string
    {
        foreach (self::OBJECT_PAGES[$script] ?? [] as [$type, $params]) {
            foreach ($params as $param) {
                $object_id = (int) ($_GET[$param] ?? 0);
                if ($object_id <= 0) {
                    continue;
                }

                $item = $this->libraryItemLoader->load(LibraryItemEnum::from($type), $object_id);
                $name = $item?->get_fullname();
                if ($name !== null && $name !== '') {
                    return $this->withIcon(self::TYPE_ICONS[$type], $name);
                }
            }
        }

        // pages that list rather than show, where the listed type is the only context there is
        if ($script === 'browse' || $script === 'mashup') {
            $labels = [
                'song' => T_('Songs'),
                'album' => T_('Albums'),
                'album_disk' => T_('Albums'),
                'album_artist' => T_('Album Artists'),
                'artist' => T_('Artists'),
                'playlist' => T_('Playlists'),
                'smartplaylist' => T_('Smart Playlists'),
                'tag' => T_('Genres'),
                'label' => T_('Labels'),
                'live_stream' => T_('Radio Stations'),
                'podcast' => T_('Podcasts'),
                'video' => T_('Videos'),
                'catalog' => T_('Catalogs'),
            ];
            $action = (string) ($_GET['action'] ?? '');
            if (isset($labels[$action])) {
                return $this->withIcon(self::TYPE_ICONS[$action] ?? "\u{1F4DA}", $labels[$action]);
            }

            return ($script === 'mashup')
                ? $this->withIcon("\u{1F4DA}", T_('Dashboards'))
                : $this->withIcon("\u{1F4DA}", T_('Browse'));
        }

        return match ($script) {
            'search' => $this->withIcon("\u{1F50D}", T_('Search')),
            'stats' => $this->withIcon("\u{1F4CA}", T_('Statistics')),
            'preferences' => $this->withIcon("\u{2699}\u{FE0F}", T_('Preferences')),
            'upload' => $this->withIcon("\u{1F4E4}", T_('Upload')),
            'shout' => $this->withIcon("\u{1F4AC}", T_('Shoutbox')),
            'pvmsg' => $this->withIcon("\u{2709}\u{FE0F}", T_('Private Messages')),
            default => null,
        };
    }

    private function hasCustomLogo(): bool
    {
        return (bool) Preference::get_by_user($this->getUserId(), 'custom_logo_user');
    }

    /**
     * The icon is a per-user choice, so a title reads the same with or without it.
     */
    private function withIcon(string $icon, string $label): string
    {
        return (AmpConfig::get('page_title_icons'))
            ? $icon . ' ' . $label
            : $label;
    }
}
