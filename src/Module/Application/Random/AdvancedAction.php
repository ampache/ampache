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

namespace Ampache\Module\Application\Random;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AdvancedAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'advanced';

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
        $this->ui->showHeader();
        $this->ui->show(
            'show_random.inc.php',
            [
                'videoRepository' => $this->videoRepository,
                'object_ids' => array()
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
