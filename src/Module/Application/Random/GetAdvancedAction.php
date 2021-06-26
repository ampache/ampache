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

namespace Ampache\Module\Application\Random;

use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Random;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetAdvancedAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'get_advanced';

    private UiInterface $ui;

    private VideoRepositoryInterface $videoRepository;

    public function __construct(
        UiInterface $ui,
        VideoRepositoryInterface $videoRepository
    ) {
        $this->ui              = $ui;
        $this->videoRepository = $videoRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $objectIds = Random::advanced($_REQUEST['type'], $_POST);

        // We need to add them to the active playlist
        if (!empty($objectIds)) {
            foreach ($objectIds as $object_id) {
                Core::get_global('user')->playlist->add_object($object_id, 'song');
            }
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_random.inc.php',
            [
                'videoRepository' => $this->videoRepository,
                'object_ids' => $objectIds
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
