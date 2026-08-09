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

use Ampache\Gui\Form\ConfirmationView;
use Override;

/**
 * The page shown once a registration has been accepted.
 */
final class RegistrationConfirmationView extends AbstractRegisterPageView
{
    public function __construct(
        string $webPath,
        string $htmlLanguage,
        string $charset,
        string $siteTitle,
        private readonly bool $adminActivationRequired,
        private readonly bool $emailConfirmationRequired,
    ) {
        parent::__construct($webPath, $htmlLanguage, $charset, $siteTitle);
    }

    /**
     * What happens next depends on how the install gates new accounts, so the confirmation says which of
     * the three it is.
     */
    public function getConfirmation(): string
    {
        $text = T_('Return to Login Page');
        if ($this->adminActivationRequired) {
            $text = T_('Please wait for an administrator to activate your account');
        }

        if ($this->emailConfirmationRequired) {
            $text = T_('An activation key has been sent to the e-mail address you provided. Please check your e-mail for further information');
        }

        return (new ConfirmationView(
            $this->getWebPath(),
            T_('Your account has been created'),
            $text,
            $this->getWebPath() . '/login.php',
            'confirmation',
            null
        ))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('registration_confirmation.phtml');
    }
}
