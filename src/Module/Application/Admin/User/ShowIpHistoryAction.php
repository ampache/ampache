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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders a users ip history
 */
final class ShowIpHistoryAction extends AbstractUserAction
{
    /** @var string */
    public const REQUEST_KEY = 'show_ip_history';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private IpHistoryRepositoryInterface $ipHistoryRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        IpHistoryRepositoryInterface $ipHistoryRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui                  = $ui;
        $this->modelFactory        = $modelFactory;
        $this->ipHistoryRepository = $ipHistoryRepository;
        $this->configContainer     = $configContainer;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $userId      = (int)($queryParams['user_id'] ?? 0);
        $showAll     = (bool)($queryParams['all'] ?? 0);

        $user = $this->modelFactory->createUser($userId);
        if ($user->isNew()) {
            throw new ObjectNotFoundException($userId);
        }

        if ($showAll === true) {
            $history = $this->ipHistoryRepository->getHistory(
                $user,
                0
            );
        } else {
            $history = $this->ipHistoryRepository->getHistory(
                $user,
            );
        }

        $this->ui->showHeader();
        $this->ui->showBoxTop(sprintf(T_('%s IP History'), $user->get_fullname()));
        $this->ui->show(
            'show_ip_history.inc.php',
            [
                'workingUser' => $user,
                'history' => $history,
                'showAll' => $showAll,
                'webPath' => $this->configContainer->getWebPath(),
            ]
        );
        $this->ui->showBoxBottom();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
