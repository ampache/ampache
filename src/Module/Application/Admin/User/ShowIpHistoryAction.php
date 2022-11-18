<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowIpHistoryAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'show_ip_history';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private IpHistoryRepositoryInterface $ipHistoryRepository;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        IpHistoryRepositoryInterface $ipHistoryRepository
    ) {
        $this->ui                  = $ui;
        $this->modelFactory        = $modelFactory;
        $this->ipHistoryRepository = $ipHistoryRepository;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $userId = (int) $queryParams['user_id'] ?? 0;
        if ($userId < 1) {
            echo T_('You have requested an object that does not exist');
        } else {
            /* get the user and their history */
            $working_user = $this->modelFactory->createUser($userId);

            if (!isset($queryParams['all'])) {
                $history = $this->ipHistoryRepository->getHistory($userId, 0, true);
            } else {
                $history = $this->ipHistoryRepository->getHistory($userId);
            }

            $this->ui->showHeader();
            $this->ui->show(
                'show_ip_history.inc.php',
                [
                    'working_user' => $working_user,
                    'history' => $history
                ]
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
