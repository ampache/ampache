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
use Ampache\Module\System\Preference;
use Override;

/**
 * The preferences sidebar tab.
 *
 * Its help links carried `target=\"_blank\"` with the backslashes intact, so the attribute reached the
 * page malformed and neither link opened in a new tab.
 */
final class PreferencesSidebarView extends AbstractSidebarView
{
    public function __construct(
        private readonly string $webPath,
        private readonly bool $mayManage,
        private readonly bool $allowUpload,
    ) {}

    public function allowUpload(): bool
    {
        return $this->allowUpload;
    }

    /**
     * The system category is not a user preference, so it is never offered here.
     *
     * @return list<array{tab: string, label: string}>
     */
    public function getCategories(): array
    {
        $categories = [];
        foreach (Preference::get_categories() as $name) {
            if ($name === 'system') {
                continue;
            }

            $categories[] = ['tab' => $name, 'label' => T_(ucfirst($name))];
        }

        $categories[] = ['tab' => 'account', 'label' => T_('Account')];

        return $categories;
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    public function mayManage(): bool
    {
        return $this->mayManage;
    }

    public function showBrowseFilter(): bool
    {
        return (bool) AmpConfig::get('browse_filter');
    }

    public function showCookieDisclaimer(): bool
    {
        return (bool) AmpConfig::get('cookie_disclaimer');
    }

    public function showHelp(): bool
    {
        return !AmpConfig::get('simple_user_mode');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('sidebar/preferences.phtml');
    }
}
