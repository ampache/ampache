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

declare(strict_types=1);

namespace Ampache\Module\Application\Artist;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAllSongsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_all_songs';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        SongRepositoryInterface $songRepository
    ) {
        $this->modelFactory   = $modelFactory;
        $this->ui             = $ui;
        $this->songRepository = $songRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $artistId = (int) ($request->getQueryParams()['artist'] ?? 0);

        $artist = $this->modelFactory->createArtist($artistId);
        $artist->format();

        $this->ui->showHeader();
        $this->ui->show(
            'show_artist.inc.php',
            [
                'artist' => $artist,
                'object_type' => 'song',
                'object_ids' => $this->songRepository->getByArtist($artistId),
                'gatekeeper' => $gatekeeper
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
