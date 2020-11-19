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

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'confirm_delete';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
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
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $this->ui->showHeader();

        $msgs = explode(',', $_REQUEST['msgs']);
        foreach ($msgs as $msg_id) {
            $msg_id = (int) ($msg_id);
            $pvmsg  = $this->modelFactory->createPrivateMsg($msg_id);

            if ($pvmsg->id && $pvmsg->to_user === Core::get_global('user')->id) {
                $pvmsg->delete();
            } else {
                $this->logger->warning(
                    sprintf('Unknown or unauthorized private message #%d.', $msg_id),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                Ui::access_denied();

                return null;
            }
        }

        show_confirmation(
            T_('No Problem'),
            T_('Messages have been deleted'),
            sprintf(
                '%s/browse.php?action=pvmsg',
                $this->configContainer->getWebPath()
            )
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
