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
use Ampache\Module\Util\Mailer;
use Override;

/**
 * The admin sidebar tab.
 */
final class AdminSidebarView extends AbstractSidebarView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $adminPath,
        private readonly bool $isAdmin,
    ) {}

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getAccessItems(): array
    {
        return [
            ['id' => 'sb_admin_access_AddAccess', 'url' => '/access.php?action=show_add_advanced', 'label' => T_('Add ACL')],
            ['id' => 'sb_admin_access_ShowAccess', 'url' => '/access.php', 'label' => T_('Show ACL(s)')],
        ];
    }

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getCatalogItems(): array
    {
        $items = [
            ['id' => 'sb_admin_catalogs_Add', 'url' => '/catalog.php?action=show_add_catalog', 'label' => T_('Add Catalog')],
            ['id' => 'sb_admin_catalogs_Show', 'url' => '/catalog.php?action=show_catalogs', 'label' => T_('Show Catalogs')],
            ['id' => 'sb_admin_ot_ExportCatalog', 'url' => '/export.php', 'label' => T_('Export Catalog')],
        ];

        if (AmpConfig::get('catalog_filter')) {
            $items[] = ['id' => 'sb_admin_filter_Add', 'url' => '/filter.php?action=show_add_filter', 'label' => T_('Add Catalog Filter')];
            $items[] = ['id' => 'sb_admin_filter_Browse', 'url' => '/filter.php', 'label' => T_('Show Catalog Filters')];
        }

        if (AmpConfig::get('licensing')) {
            $items[] = ['id' => 'sb_admin_ot_ManageLicense', 'url' => '/license.php', 'label' => T_('Manage Licenses')];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getModuleItems(): array
    {
        return [
            ['id' => 'sb_admin_modules_localplay', 'url' => '/modules.php?action=show_localplay', 'label' => T_('Localplay Controllers')],
            ['id' => 'sb_admin_modules_catalog_types', 'url' => '/modules.php?action=show_catalog_types', 'label' => T_('Catalog Types')],
            ['id' => 'sb_admin_modules_plugins', 'url' => '/modules.php?action=show_plugins', 'label' => T_('Manage Plugins')],
        ];
    }

    /**
     * Every preference category is administrable, including the system one a user never sees.
     *
     * @return list<array{tab: string, label: string}>
     */
    public function getServerCategories(): array
    {
        $categories = [];
        foreach (Preference::get_categories() as $name) {
            $categories[] = ['tab' => $name, 'label' => T_(ucfirst($name))];
        }

        return $categories;
    }

    /**
     * @return list<array{id: string, url: string, label: string}>
     */
    public function getUserItems(): array
    {
        $items = [
            ['id' => 'sb_admin_users_AddUser', 'url' => '/users.php?action=show_add_user', 'label' => T_('Add User')],
            ['id' => 'sb_admin_users_BrowseUsers', 'url' => '/users.php', 'label' => T_('Browse Users')],
        ];

        if (Mailer::is_mail_enabled()) {
            $items[] = ['id' => 'sb_admin_ot_Mail', 'url' => '/mail.php', 'label' => T_('E-mail Users')];
        }

        if (AmpConfig::get('allow_upload')) {
            $items[] = ['id' => 'sb_admin_users_Uploads', 'url' => '/uploads.php', 'label' => T_('Browse Uploads')];
        }

        if (AmpConfig::get('sociable')) {
            $items[] = ['id' => 'sb_admin_ot_ManageShoutbox', 'url' => '/shout.php', 'label' => T_('Manage Shoutbox')];
        }

        $items[] = ['id' => 'sb_admin_ot_ClearNowPlaying', 'url' => '/catalog.php?action=clear_now_playing', 'label' => T_('Clear Now Playing')];

        return $items;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function showBrowseFilter(): bool
    {
        return (bool) AmpConfig::get('browse_filter');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('sidebar/admin.phtml');
    }
}
