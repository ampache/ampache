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

namespace Ampache\Module\Application\Browse;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PrivateMessageAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'pvmsg';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->modelFactory = $modelFactory;
        $this->ui           = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        session_start();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(static::REQUEST_KEY);
        $browse->set_simple_browse(true);

        $this->ui->showHeader();

        // Browser is able to save page on current session. Only applied to main menus.
        $browse->set_update_session(true);

        $browse->set_sort('creation_date', 'DESC');
        $folder = $_REQUEST['folder'] ?? null;
        if ($folder === 'sent') {
            $browse->set_filter('user', Core::get_global('user')->id);
        } else {
            $browse->set_filter('to_user', Core::get_global('user')->id);
        }
        $browse->update_browse_from_session();
        $browse->show_objects();

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
