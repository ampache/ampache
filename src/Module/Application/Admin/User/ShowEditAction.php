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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowEditAction extends AbstractUserAction
{
    public const REQUEST_KEY = 'show_edit';

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

        $this->ui->showHeader();

        $client = $this->modelFactory->createUser((int) Core::get_request('user_id'));
        $client->format();

        $this->ui->show(
            'show_edit_user.inc.php',
            [$client]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
