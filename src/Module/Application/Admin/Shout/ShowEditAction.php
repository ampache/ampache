<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Displays the shouts edit-view
 */
final class ShowEditAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_edit';

    private UiInterface $ui;

    private ShoutRepositoryInterface $shoutRepository;

    private ModelFactoryInterface $modelFactory;

    private ShoutObjectLoaderInterface $shoutObjectLoader;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ShoutObjectLoaderInterface $shoutObjectLoader,
        ShoutRepositoryInterface $shoutRepository
    ) {
        $this->ui                = $ui;
        $this->modelFactory      = $modelFactory;
        $this->shoutObjectLoader = $shoutObjectLoader;
        $this->shoutRepository   = $shoutRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException();
        }

        $shoutId = (int)($request->getQueryParams()['shout_id'] ?? 0);
        $shout   = $this->shoutRepository->findById($shoutId);

        if ($shout === null) {
            throw new ObjectNotFoundException($shoutId);
        }

        // load the object the shout is referring to
        $object = $this->shoutObjectLoader->loadByShout($shout);
        if ($object === null) {
            throw new ObjectNotFoundException($shout->getObjectId());
        }

        // load the used who created the shout
        $shoutUserId = $shout->getUserId();

        $user = $this->modelFactory->createUser($shoutUserId);
        if ($user->isNew()) {
            throw new ObjectNotFoundException($shoutUserId);
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_edit_shout.inc.php',
            [
                'shout' => $shout,
                'object' => $object,
                'client' => $user,
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
