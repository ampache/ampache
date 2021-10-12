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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConfirmEnableAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'confirm_enable';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private UserStateTogglerInterface $userStateToggler;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UserStateTogglerInterface $userStateToggler
    ) {
        $this->ui               = $ui;
        $this->modelFactory     = $modelFactory;
        $this->configContainer  = $configContainer;
        $this->userStateToggler = $userStateToggler;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        if (!Core::form_verify('enable_user')) {
            throw new AccessDeniedException();
        }
        $this->ui->showHeader();

        $user = $this->modelFactory->createUser((int) Core::get_request('user_id'));

        $this->userStateToggler->enable($user);

        $this->ui->showConfirmation(
            T_('No Problem'),
            /* HINT: Username and fullname together: Username (fullname) */
            sprintf(T_('%s (%s) has been enabled'), $user->username, $user->fullname),
            'admin/users.php'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
