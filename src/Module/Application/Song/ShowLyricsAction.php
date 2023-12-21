<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Song;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowLyricsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_lyrics';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        $this->requestParser = $requestParser;
        $this->ui            = $ui;
        $this->modelFactory  = $modelFactory;
    }

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        $this->ui->showHeader();

        $song_id = (int)$this->requestParser->getFromRequest('song_id');
        $song    = $this->modelFactory->createSong($song_id);
        $song->format();
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics();

        $this->ui->show(
            'show_lyrics.inc.php',
            [
                'song' => $song,
                'lyrics' => $lyrics
            ]
        );

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
