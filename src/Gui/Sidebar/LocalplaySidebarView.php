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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Override;

/**
 * The localplay sidebar tab.
 *
 * Its disabled heading took the collapse class straight from a cookie without escaping it.
 */
final class LocalplaySidebarView extends AbstractSidebarView
{
    private ?LocalPlay $localplay = null;

    public function __construct(
        private readonly string $webPath,
    ) {}

    public function getCurrentInstance(): int
    {
        return (int) $this->getLocalplay()->current_instance();
    }

    /**
     * Says which of the three reasons localplay is unavailable, so the sidebar can name it.
     */
    public function getDisabledReason(): string
    {
        if (!AmpConfig::get('allow_localplay_playback')) {
            return T_('Allow Localplay Set to False');
        }

        return (AmpConfig::get('localplay_controller'))
            ? T_('Access Denied')
            : T_('Localplay Controller Not Defined');
    }

    /**
     * @return array<int|string, string>
     */
    public function getInstances(): array
    {
        return $this->getLocalplay()->get_instances();
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isEnabled(): bool
    {
        return (bool) AmpConfig::get('allow_localplay_playback')
            && (bool) AmpConfig::get('localplay_controller')
            && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST);
    }

    public function mayManage(): bool
    {
        return Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::MANAGER);
    }

    public function mayUse(): bool
    {
        return Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::USER);
    }

    public function showBrowseFilter(): bool
    {
        return (bool) AmpConfig::get('browse_filter');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('sidebar/localplay.phtml');
    }

    private function getLocalplay(): LocalPlay
    {
        if ($this->localplay === null) {
            $this->localplay = new LocalPlay((string) AmpConfig::get('localplay_controller'));
        }

        return $this->localplay;
    }
}
