<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Application\Register;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAddUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_user';

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    /**
     * @todo drop copy/paste code from register action after fixing the captcha problam
     */
    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        /* Check Perms */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === false &&
            !Mailer::is_mail_enabled()
        ) {
            $this->logger->error(
                'Error attempted registration',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            Ui::access_denied();

            return null;
        }

        /* Don't even include it if we aren't going to use it */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CAPTCHA_PUBLIC_REG) === true) {
            define('CAPTCHA_INVERSE', 1);
            /**
             * @todo broken, the path does not exist anylonger
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
        require_once Ui::find_template('show_user_registration.inc.php');

        return null;
    }
}
