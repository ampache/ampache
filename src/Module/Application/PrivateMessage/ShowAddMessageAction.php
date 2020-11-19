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

namespace Ampache\Module\Application\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAddMessageAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_message';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->logger          = $logger;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            !Access::check('interface', 25) ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) === false
        ) {
            $this->logger->warning(
                'Access Denied: sociable features are not enabled.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            Ui::access_denied();

            return null;
        }

        $this->ui->showHeader();

        if (isset($_REQUEST['reply_to'])) {
            $pvmsg = $this->modelFactory->createPrivateMsg((int) $_REQUEST['reply_to']);
            if ($pvmsg->id && ($pvmsg->from_user === Core::get_global('user')->id || $pvmsg->to_user === Core::get_global('user')->id)) {
                $to_user             = $this->modelFactory->createUser($pvmsg->from_user);
                $_REQUEST['to_user'] = $to_user->username;
                /* HINT: Shorthand for e-mail reply */
                $_REQUEST['subject'] = T_('RE') . ": " . $pvmsg->subject;
                $_REQUEST['message'] = "\n\n\n---\n> " . str_replace("\n", "\n> ", $pvmsg->message);
            }
        }
        require_once Ui::find_template('show_add_pvmsg.inc.php');

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
