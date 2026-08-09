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
        return (string) ($this->currentUser->fullname ?? '');
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
        return (new RightbarView(
            $this->collectionRepository,
            $this->libraryItemLoader,
            $this->playlistLoader,
            $this->zipHandler,
            $this->webPath
        ))->render();
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
        return (int) AmpConfig::get('int_config_version') > (int) AmpConfig::get('config_version');
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

    private function hasCustomLogo(): bool
    {
        return (bool) Preference::get_by_user($this->getUserId(), 'custom_logo_user');
    }
}
