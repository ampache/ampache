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

namespace Ampache\Module\Application\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAddMessageAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_message';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private PrivateMessageRepositoryInterface $privateMessageRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        PrivateMessageRepositoryInterface $privateMessageRepository
    ) {
        $this->configContainer          = $configContainer;
        $this->ui                       = $ui;
        $this->modelFactory             = $modelFactory;
        $this->privateMessageRepository = $privateMessageRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) === false
        ) {
            throw new AccessDeniedException('Access Denied: sociable features are not enabled.');
        }

        $replyToMessageId = (int) ($request->getQueryParams()['reply_to'] ?? 0);

        if ($replyToMessageId > 0) {
            $message = $this->privateMessageRepository->findById($replyToMessageId);

            if ($message !== null) {
                $userId       = $gatekeeper->getUserId();
                $senderUserId = $message->getSenderUserId();

                if (
                    $senderUserId === $userId ||
                    $message->getRecipientUserId() === $userId
                ) {
                    $to_user             = $this->modelFactory->createUser($senderUserId);
                    $_REQUEST['to_user'] = $to_user->username;
                    /* HINT: Shorthand for e-mail reply */
                    $_REQUEST['subject'] = sprintf('%s: %s', T_('RE'), $message->getSubject());
                    $_REQUEST['message'] = "\n\n\n---\n> " . str_replace("\n", "\n> ", $message->getMessage());
                }
            }
        }

        $this->ui->showHeader();
        $this->ui->show('show_add_pvmsg.inc.php');
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
