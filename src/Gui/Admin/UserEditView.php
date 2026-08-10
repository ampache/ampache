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

namespace Ampache\Gui\Admin;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\User;
use Override;

/**
 * The admin's edit-user page.
 *
 * Its username, full name and api key reached their `value=` attribute unescaped, and the api key was
 * also interpolated into a javascript string literal. A stray `preset` literal sat outside its option
 * tag, and the avatar upload cell never closed.
 */
final class UserEditView extends AbstractView
{
    public function __construct(
        private readonly User $client,
        private readonly string $adminPath,
        private readonly bool $isAdmin,
    ) {}

    public function getAccessLevel(): int
    {
        return (int) $this->client->access;
    }

    /**
     * @return array<int, string>
     */
    public function getAccessLevels(): array
    {
        return [
            5 => T_('Guest'),
            25 => T_('User'),
            50 => T_('Content Manager'),
            75 => T_('Catalog Manager'),
            100 => T_('Admin'),
        ];
    }

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    public function getApikey(): string
    {
        return (string) $this->client->apikey;
    }

    public function getAvatar(): string
    {
        return $this->client->get_f_avatar('f_avatar');
    }

    public function getCatalogFilterGroup(): int
    {
        return (int) $this->client->catalog_filter_group;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function getCatalogFilters(): array
    {
        $filters = [];
        foreach (Catalog::get_catalog_filters() as $filter) {
            $filters[] = ['id' => (int) $filter['id'], 'name' => (string) $filter['name']];
        }

        return $filters;
    }

    public function getCity(): string
    {
        return (string) $this->client->city;
    }

    public function getEmail(): string
    {
        return (string) $this->client->email;
    }

    public function getFormAction(): string
    {
        return $this->adminPath . '/users.php';
    }

    public function getFullname(): string
    {
        return (string) $this->client->fullname;
    }

    public function getMaxUploadSize(): int
    {
        return AmpConfig::get_int('max_upload_size');
    }

    /**
     * @return array<string, string>
     */
    public function getPresets(): array
    {
        return [
            '' => '',
            'system' => T_('System'),
            'default' => T_('Default'),
            'minimalist' => T_('Minimalist'),
            'community' => T_('Community'),
        ];
    }

    public function getRssToken(): string
    {
        return (string) $this->client->rsstoken;
    }

    public function getState(): string
    {
        return (string) $this->client->state;
    }

    public function getStreamToken(): string
    {
        return (string) $this->client->streamtoken;
    }

    public function getUserId(): int
    {
        return $this->client->getId();
    }

    public function getUsername(): string
    {
        return (string) $this->client->username;
    }

    public function getWebsite(): string
    {
        return (string) $this->client->website;
    }

    public function hasSubsonicSecret(): bool
    {
        return (bool) $this->client->subsonic_secret;
    }

    /**
     * The stream and rss token controls are admin only; `AbstractUserAction` already gates both callers.
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isFullnamePublic(): bool
    {
        return (bool) $this->client->fullname_public;
    }

    public function showCatalogFilter(): bool
    {
        return (bool) AmpConfig::get('catalog_filter');
    }

    /**
     * A preset rewrites every preference, so it is never offered for an admin account.
     */
    public function showPresets(): bool
    {
        return $this->getAccessLevel() !== 100;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('admin/user_edit.phtml');
    }
}
