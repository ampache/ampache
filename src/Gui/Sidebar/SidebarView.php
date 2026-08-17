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
use Ampache\Repository\MoodRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The sidebar tab strip and the body of whichever tab is open.
 *
 * The open tab is session state, so it survives navigation; an unregistered visitor gets the home tab
 * with no strip at all.
 */
final class SidebarView extends AbstractSidebarView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $adminPath,
        private readonly string $albumType,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly FolderRepositoryInterface $folderRepository,
        private readonly MoodRepositoryInterface $moodRepository,
        private readonly string $activeTab,
        private readonly string $sessionId,
        private readonly bool $isRegistered,
        private readonly bool $isSession,
        private readonly bool $mayUse,
        private readonly bool $mayManage,
        private readonly bool $mayAdmin,
        private readonly bool $mayAccessGuest,
        private readonly bool $mayAccessManager,
        private readonly bool $localplayAvailable,
        private readonly bool $allowUpload,
    ) {}

    public function getActiveTab(): string
    {
        return $this->activeTab;
    }

    /**
     * The `Cookies.set` options the collapse script writes its state with.
     */
    public function getCookieOptions(): string
    {
        return (AmpConfig::get('cookie_secure'))
            ? "expires: 30, path: '/', secure: true, samesite: 'Strict'"
            : "expires: 30, path: '/', samesite: 'Strict'";
    }

    public function getLogoutUrl(): string
    {
        return $this->webPath . '/logout.php?session=' . $this->sessionId;
    }

    /**
     * @return list<array{id: string, title: string, icon: string}>
     */
    public function getTabs(): array
    {
        $tabs = [];
        if ($this->mayAccessGuest) {
            $tabs[] = ['id' => 'home', 'title' => T_('Home'), 'icon' => 'headphones'];

            if ($this->localplayAvailable) {
                $tabs[] = ['id' => 'localplay', 'title' => T_('Localplay'), 'icon' => 'volume_up'];
            }

            if ($this->isSession) {
                $tabs[] = ['id' => 'preferences', 'title' => T_('Preferences'), 'icon' => 'page_info'];
            }
        }

        if ($this->mayAccessManager) {
            $tabs[] = ['id' => 'admin', 'title' => T_('Admin'), 'icon' => 'dns'];
        }

        return $tabs;
    }

    public function isActive(string $tabId): bool
    {
        return $tabId === $this->activeTab;
    }

    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }

    /**
     * The body of a tab, rendered by the view that owns it.
     */
    public function renderTab(string $tabId): string
    {
        return match ($tabId) {
            'admin' => new AdminSidebarView($this->webPath, $this->adminPath, $this->mayAdmin)->render(),
            'localplay' => new LocalplaySidebarView($this->webPath)->render(),
            'preferences' => new PreferencesSidebarView($this->webPath, $this->mayManage, $this->allowUpload)->render(),
            default => $this->renderHome(),
        };
    }

    /**
     * A manager-less account still sees the admin tab as a padlock, unless the install hides it entirely.
     */
    public function showAdminLocked(): bool
    {
        return !$this->mayAccessManager && !AmpConfig::get('simple_user_mode');
    }

    public function showLogout(): bool
    {
        return $this->isSession && $this->sessionId !== '';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('sidebar/sidebar.phtml');
    }

    private function renderHome(): string
    {
        return new HomeSidebarView(
            $this->webPath,
            $this->albumType,
            $this->videoRepository,
            $this->folderRepository,
            $this->moodRepository,
            $this->mayUse,
            $this->mayManage,
            $this->allowUpload
        )->render();
    }
}
