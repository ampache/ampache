<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Application\Admin\Mail;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SendMailAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'send_mail';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->requestParser   = $requestParser;
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // Multi-byte Character Mail
        if (function_exists('mb_language')) {
            $ini_default_charset = 'default_charset';
            if (ini_get($ini_default_charset)) {
                ini_set($ini_default_charset, "UTF-8");
            }
            mb_language("uni");
        }

        if (Mailer::is_mail_enabled()) {
            $mailer = new Mailer();

            // Set the vars on the object
            $mailer->subject = $this->requestParser->getFromRequest('subject');
            $mailer->message = $this->requestParser->getFromRequest('message');

            if ($this->requestParser->getFromRequest('from') == 'system') {
                $mailer->set_default_sender();
            } else {
                $mailer->sender      = Core::get_global('user')->email;
                $mailer->sender_name = Core::get_global('user')->fullname;
            }

            if ($mailer->send_to_group($this->requestParser->getFromRequest('to'))) {
                $title = T_('No Problem');
                $body  = T_('Your e-mail has been sent');
            } else {
                $title = T_('There Was a Problem');
                $body  = T_('Your e-mail has not been sent');
            }

            $url = sprintf(
                '%s/admin/mail.php',
                $this->configContainer->getWebPath()
            );
            $this->ui->showConfirmation($title, $body, $url);
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
