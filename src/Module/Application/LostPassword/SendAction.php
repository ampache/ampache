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

namespace Ampache\Module\Application\LostPassword;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\User\NewPasswordSenderInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SendAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'send';

    private ConfigContainerInterface $configContainer;

    private NewPasswordSenderInterface $newPasswordSender;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        NewPasswordSenderInterface $newPasswordSender,
        UiInterface $ui
    ) {
        $this->configContainer   = $configContainer;
        $this->newPasswordSender = $newPasswordSender;
        $this->ui                = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            !Mailer::is_mail_enabled() ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
        ) {
            throw new AccessDeniedException();
        }

        if (isset($_POST['email']) && Core::get_post('email')) {
            /* Get the email address and the current ip*/
            $email      = scrub_in((string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $current_ip = Core::get_user_ip();
            $this->newPasswordSender->send($email, $current_ip);
        }
        // Do not acknowledge a password has been sent or failed and go back to login
        $this->ui->show('show_login_form.inc.php');

        return null;
    }
}
