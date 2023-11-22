<?php

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

declare(strict_types=0);

namespace Ampache\Module\Application\Stats;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class WantedAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'wanted';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private WantedRepositoryInterface $wantedRepository;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        WantedRepositoryInterface $wantedRepository
    ) {
        $this->ui               = $ui;
        $this->modelFactory     = $modelFactory;
        $this->wantedRepository = $wantedRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        $this->ui->showBoxTop(T_('Information'));

        $userId = null;
        if (empty(Core::get_global('user')) || !Core::get_global('user')->has_access(75)) {
            $userId = Core::get_global('user')->id;
        }

        $object_ids = $this->wantedRepository->getAll($userId);

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type('wanted');
        $browse->set_static_content(true);
        $browse->save_objects($object_ids);
        $browse->show_objects($object_ids);
        $browse->store();

        $this->ui->showBoxBottom();

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
