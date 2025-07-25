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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the user-enable confirmation
 */
final class EnableAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'enable';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $userId = (int)($request->getQueryParams()['user_id'] ?? 0);
        $user   = $this->modelFactory->createUser($userId);

        if ($user->isNew()) {
            throw new ObjectNotFoundException($userId);
        }

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('Are You Sure?'),
            /* HINT: User Fullname */
            sprintf(T_('This will enable the user "%s"'), scrub_out($user->getFullDisplayName())),
            sprintf(
                '%s/users.php?action=confirm_enable&user_id=%s',
                $this->configContainer->getWebPath('/admin'),
                $userId
            ),
            1,
            'enable_user'
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
