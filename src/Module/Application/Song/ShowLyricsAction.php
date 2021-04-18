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

namespace Ampache\Module\Application\Song;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowLyricsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_lyrics';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        $this->ui           = $ui;
        $this->modelFactory = $modelFactory;
    }

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        $songId = (int) ($request->getQueryParams()['song_id'] ?? 0);

        $song = $this->modelFactory->createSong($songId);
        $song->format();
        $song->fill_ext_info();

        $this->ui->showHeader();
        $this->ui->show(
            'show_lyrics.inc.php',
            [
                'song' => $song,
                'lyrics' => $song->get_lyrics()
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
