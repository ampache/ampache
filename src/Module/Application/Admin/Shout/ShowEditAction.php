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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutParentObjectLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowEditAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_edit';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ShoutParentObjectLoaderInterface $shoutParentObjectLoader;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ShoutParentObjectLoaderInterface $shoutParentObjectLoader
    ) {
        $this->ui                      = $ui;
        $this->modelFactory            = $modelFactory;
        $this->shoutParentObjectLoader = $shoutParentObjectLoader;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException();
        }

        $shout = $this->modelFactory->createShoutbox(
            (int) ($request->getQueryParams()['shout_id'] ?? 0)
        );
        $object = $this->shoutParentObjectLoader->load($shout->getObjectType(), $shout->getObjectId());
        $object->format();

        $client = $this->modelFactory->createUser($shout->getUserId());
        $client->format();

        $this->ui->showHeader();
        $this->ui->show(
            'show_edit_shout.inc.php',
            [
                'client' => $client,
                'object' => $object,
                'shout' => $shout,
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
