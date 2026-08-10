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

namespace Ampache\Gui\Register;

use Override;

/**
 * The page a registration validation link lands on.
 */
final class UserActivationView extends AbstractRegisterPageView
{
    public function __construct(
        string $webPath,
        string $htmlLanguage,
        string $charset,
        string $siteTitle,
        private readonly bool $activated,
    ) {
        parent::__construct($webPath, $htmlLanguage, $charset, $siteTitle);
    }

    public function getLoginUrl(): string
    {
        return $this->getWebPath() . '/login.php';
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('user_activation.phtml');
    }
}
