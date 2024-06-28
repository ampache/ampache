<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Register;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\Registration\RegistrationAgreementRendererInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAddUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_user';

    private ConfigContainerInterface $configContainer;

    private RegistrationAgreementRendererInterface $registrationAgreementRenderer;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        RegistrationAgreementRendererInterface $registrationAgreementRenderer,
        UiInterface $ui
    ) {
        $this->configContainer               = $configContainer;
        $this->registrationAgreementRenderer = $registrationAgreementRenderer;
        $this->ui                            = $ui;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GuiGatekeeperInterface $gatekeeper
     * @return ResponseInterface|null
     */
    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Check allow_public_registration
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === false
        ) {
            throw new AccessDeniedException('Error `allow_public_registration` disabled');
        }
        // Check for confirmation email requirements when mail is disabled
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === true &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false &&
            !Mailer::is_mail_enabled()
        ) {
            throw new AccessDeniedException('Error `mail_enable` failed. Enable `user_no_email_confirm` to disable mail requirements');
        }

        /* Don't even include it if we aren't going to use it */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CAPTCHA_PUBLIC_REG) === true) {
            define('CAPTCHA_INVERSE', 1);
            /**
             * @todo broken, the path does not exist any longer
             */
            define(
                'CAPTCHA_BASE_URL',
                sprintf(
                    '%s/modules/captcha/captcha.php',
                    $this->configContainer->getWebPath()
                )
            );
        }

        $this->ui->show(
            'show_user_registration.inc.php',
            [
                'registrationAgreementRenderer' => $this->registrationAgreementRenderer,
            ]
        );

        return null;
    }
}
