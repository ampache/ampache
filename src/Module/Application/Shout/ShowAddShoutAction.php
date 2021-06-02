<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Shout;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutParentObjectLoaderInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAddShoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_shout';

    private UiInterface $ui;

    private ShoutRepositoryInterface $shoutRepository;

    private ShoutParentObjectLoaderInterface $shoutParentObjectLoader;

    public function __construct(
        UiInterface $ui,
        ShoutRepositoryInterface $shoutRepository,
        ShoutParentObjectLoaderInterface $shoutParentObjectLoader
    ) {
        $this->ui                      = $ui;
        $this->shoutRepository         = $shoutRepository;
        $this->shoutParentObjectLoader = $shoutParentObjectLoader;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Get our object first
        $object = $this->shoutParentObjectLoader->load(
            $_REQUEST['type'],
            (int) Core::get_request('id')
        );

        $this->ui->showHeader();

        if (!$object || !$object->id) {
            AmpError::add('general', T_('Invalid object selected'));
            echo AmpError::display('general');

            $this->ui->showQueryStats();
            $this->ui->showHeader();

            return null;
        }

        $data = null;

        $object->format();
        if (get_class($object) == Song::class) {
            $data = $_REQUEST['offset'];
        }
        $object_type = ObjectTypeToClassNameMapper::reverseMap(get_class($object));

        // Now go ahead and display the page where we let them add a comment etc
        $this->ui->show(
            'show_add_shout.inc.php',
            [
                'data' => $data,
                'object_type' => $object_type,
                'object_id' => $object->getId(),
                'object_title' => $object->f_title,
                'shouts' => $this->shoutRepository->getBy($object_type, $object->getId())
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showHeader();

        return null;
    }
}
