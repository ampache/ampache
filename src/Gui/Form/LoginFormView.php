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

namespace Ampache\Gui\Form;

use Override;

/**
 * The login page.
 *
 * Everything the old template worked out for itself -- the referrer to carry through, whether the OIDC
 * auto-redirect applies, the session and header side effects -- is decided by `LoginFormViewFactory` before
 * this view exists, so the template only prints.
 */
final class LoginFormView extends AbstractFormView
{
    public function __construct(
        string $webPath,
        private readonly string $htmlLanguage,
        private readonly string $direction,
        private readonly string $charset,
        private readonly string $siteTitle,
        private readonly string $logoUrl,
        private readonly string $username,
        private readonly string $referrer,
        private readonly string $loginMessage,
        private readonly string $miniPlayerUrl,
        private readonly string $oidcButtonText,
        private readonly bool $mobileSession,
        private readonly bool $rememberDisabled,
        private readonly bool $miniPlayerReferrer,
        private readonly bool $registrationEnabled,
        private readonly bool $lostPasswordEnabled,
        private readonly bool $miniPlayerEnabled,
        private readonly bool $oidcEnabled,
        private readonly bool $cookieDisclaimerEnabled,
    ) {
        parent::__construct($webPath);
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getHtmlLanguage(): string
    {
        return $this->htmlLanguage;
    }

    /**
     * Operator-supplied markup shown above the login options; deliberately not escaped.
     */
    public function getLoginMessage(): string
    {
        return $this->loginMessage;
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    public function getMiniPlayerUrl(): string
    {
        return $this->miniPlayerUrl;
    }

    public function getOidcButtonText(): string
    {
        return $this->oidcButtonText;
    }

    public function getReferrer(): string
    {
        return $this->referrer;
    }

    public function getSiteTitle(): string
    {
        return $this->siteTitle;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isCookieDisclaimerEnabled(): bool
    {
        return $this->cookieDisclaimerEnabled;
    }

    public function isLostPasswordEnabled(): bool
    {
        return $this->lostPasswordEnabled;
    }

    public function isMiniPlayerEnabled(): bool
    {
        return $this->miniPlayerEnabled;
    }

    public function isMiniPlayerReferrer(): bool
    {
        return $this->miniPlayerReferrer;
    }

    public function isMobileSession(): bool
    {
        return $this->mobileSession;
    }

    public function isOidcEnabled(): bool
    {
        return $this->oidcEnabled;
    }

    public function isRegistrationEnabled(): bool
    {
        return $this->registrationEnabled;
    }

    public function isRememberDisabled(): bool
    {
        return $this->rememberDisabled;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/login.phtml');
    }
}
