<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Video;

use Ampache\Repository\Model\Video;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowVideoAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_video';

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui
    ) {
        $this->ui = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $video = new Video((int)filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS));

        $this->ui->show(
            'show_video.inc.php',
            ['video' => $video]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
