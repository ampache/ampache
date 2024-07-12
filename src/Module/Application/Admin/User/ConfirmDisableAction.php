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
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Disables a user
 */
final class ConfirmDisableAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'confirm_disable';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private UserStateTogglerInterface $userStateToggler;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UserStateTogglerInterface $userStateToggler
    ) {
        $this->requestParser    = $requestParser;
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

        if ($this->requestParser->verifyForm('disable_user') === false) {
            throw new AccessDeniedException();
        }

        $userId = (int)$request->getQueryParams()['user_id'];
        $user   = $this->modelFactory->createUser($userId);

        if ($user->isNew()) {
            throw new ObjectNotFoundException($userId);
        }

        $this->ui->showHeader();
        if ($this->userStateToggler->disable($user) === true) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                sprintf(T_('%s has been disabled'), scrub_out($user->getFullDisplayName())),
                'admin/users.php'
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                T_('You need at least one active Administrator account'),
                'admin/users.php'
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
