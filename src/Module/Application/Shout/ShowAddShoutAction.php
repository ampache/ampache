<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Shout;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAddShoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_add_shout';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ShoutRepositoryInterface $shoutRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ShoutRepositoryInterface $shoutRepository
    ) {
        $this->requestParser   = $requestParser;
        $this->ui              = $ui;
        $this->shoutRepository = $shoutRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $object_type = $this->requestParser->getFromRequest('type');
        $object_id   = (int)$this->requestParser->getFromRequest('id');
        // Get our object first
        $object = Shoutbox::get_object($object_type, $object_id);

        $this->ui->showHeader();

        if (!$object || !$object->id) {
            AmpError::add('general', T_('Invalid object selected'));
            echo AmpError::display('general');

            $this->ui->showQueryStats();
            $this->ui->showHeader();

            return null;
        }
        $object->format();

        $data = '';
        if (get_class($object) == Song::class) {
            $data = $this->requestParser->getFromRequest('offset');
        }
        $object_type = ObjectTypeToClassNameMapper::reverseMap(get_class($object));
        $shouts      = $this->shoutRepository->getBy($object_type, $object->id);

        // Now go ahead and display the page where we let them add a comment etc
        $this->ui->show(
            'show_add_shout.inc.php',
            [
                'data' => $data,
                'object' => $object,
                'object_type' => $object_type,
                'shouts' => $shouts
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showHeader();

        return null;
    }
}
