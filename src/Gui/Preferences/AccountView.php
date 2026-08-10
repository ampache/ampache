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

namespace Ampache\Gui\Preferences;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Override;

/**
 * The account tab: the editable form, or its read-only twin in simple user mode.
 *
 * Both pasted the api key into a javascript string literal.
 */
final class AccountView extends AbstractView
{
    public function __construct(
        private readonly User $client,
        private readonly string $webPath,
        private readonly bool $readOnly,
    ) {}

    public function getAdminPath(): string
    {
        return AmpConfig::get_web_path('/admin');
    }

    public function getApikey(): string
    {
        return (string) $this->client->apikey;
    }

    public function getAvatar(): string
    {
        return $this->client->get_f_avatar('f_avatar');
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
        return $this->webPath . '/preferences.php?action=update_user';
    }

    public function getFullname(): string
    {
        return (string) $this->client->fullname;
    }

    public function getMaxUploadSize(): int
    {
        return AmpConfig::get_int('max_upload_size');
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

    public function getTab(): string
    {
        return (string) Core::get_request('tab');
    }

    public function getUserId(): int
    {
        return $this->client->getId();
    }

    public function getWebsite(): string
    {
        return (string) $this->client->website;
    }

    public function hasSubsonicSecret(): bool
    {
        return (bool) $this->client->subsonic_secret;
    }

    public function isAdmin(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
    }

    /**
     * Simple user mode shows the same details without letting them be changed.
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * An install decides which of these a user may see at all.
     */
    public function showField(string $field): bool
    {
        return in_array($field, AmpConfig::get_array('registration_display_fields'), true);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('preferences/account.phtml');
    }
}
