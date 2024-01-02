<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ValidateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'validate';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        UserRepositoryInterface $userRepository
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->userRepository  = $userRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Check Perms */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === false ||
            ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === true && !Mailer::is_mail_enabled())
        ) {
            throw new AccessDeniedException('Error attempted registration');
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
            require_once __DIR__ . '/../../Util/Captcha/init.php';
        }

        $username           = trim(scrub_in(Core::get_get('username')));
        $validation         = trim(scrub_in(Core::get_get('auth')));
        $userValidationCode = $this->userRepository->getValidationByUsername($username);

        if ($validation !== '' && $validation === $userValidationCode) {
            $this->userRepository->activateByUsername($username);
            $validationResult = true;
        } else {
            $validationResult = false;
        }

        $this->ui->show(
            'show_user_activate.inc.php',
            [
                'validationResult' => $validationResult
            ]
        );

        return null;
    }
}
