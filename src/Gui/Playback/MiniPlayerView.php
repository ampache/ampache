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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\Session;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Override;

/**
 * The mini player page, which a user locked out of the full interface gets instead.
 *
 * It carries the play type switcher and everything each type needs, so the switcher can stick.
 */
final class MiniPlayerView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly EnvironmentInterface $environment,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly PlaylistLoaderInterface $playlistLoader,
        private readonly ZipHandlerInterface $zipHandler,
    ) {}

    /**
     * The theme swaps the loading spinner per colour, the same test the player headers make.
     */
    public function getAjaxLoaderUrl(): string
    {
        $suffix = (AmpConfig::get('theme_color', 'dark') === 'light') ? '-light' : '';

        return $this->webPath . AmpConfig::get('theme_path', '/themes/reborn') . '/images/ajax-loader' . $suffix . '.gif';
    }

    public function getAjaxUriRetriever(): AjaxUriRetrieverInterface
    {
        return $this->ajaxUriRetriever;
    }

    public function getCollectionRepository(): CollectionRepositoryInterface
    {
        return $this->collectionRepository;
    }

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', $this->getSiteLanguage());
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment;
    }

    public function getLibraryItemLoader(): LibraryItemLoaderInterface
    {
        return $this->libraryItemLoader;
    }

    public function getLogoUrl(): string
    {
        return Ui::get_logo_url();
    }

    public function getPlaylistLoader(): PlaylistLoaderInterface
    {
        return $this->playlistLoader;
    }

    public function getSessionId(): string
    {
        return (string) Session::get();
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

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    public function getZipHandler(): ZipHandlerInterface
    {
        return $this->zipHandler;
    }

    public function renderPlayerHeaders(): string
    {
        return (new WebPlayerHeadersView($this->webPath, $this->ajaxUriRetriever, true))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/mini_player.phtml');
    }
}
