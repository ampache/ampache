<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private PrivateMessageRepositoryInterface $privateMessageRepository;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        PrivateMessageRepositoryInterface $privateMessageRepository
    ) {
        $this->ui                       = $ui;
        $this->configContainer          = $configContainer;
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

        $this->ui->showHeader();

        $msgId = (int)($request->getQueryParams()['pvmsg_id'] ?? 0);

        $pvmsg = $this->privateMessageRepository->findById($msgId);

        if (
            $pvmsg === null ||
            $pvmsg->getRecipientUserId() !== $gatekeeper->getUserId()
        ) {
            throw new AccessDeniedException(
                sprintf('Unknown or unauthorized private message #%d.', $msgId),
            );
        }
        if ($pvmsg->isRead() === false) {
            $this->privateMessageRepository->setIsRead($pvmsg, 1);
        }

        $this->ui->show(
            'show_pvmsg.inc.php',
            ['pvmsg' => $pvmsg]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
