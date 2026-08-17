<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Artist\ArtistPageView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowSongsAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_songs';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private SongRepositoryInterface $songRepository,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $artistId = (int) ($request->getQueryParams()['artist'] ?? 0);

        $artist = $this->modelFactory->createArtist($artistId);

        $this->ui->showHeader();
        echo new ArtistPageView(
            $artist,
            ['' => $this->songRepository->getByArtist($artistId)],
            'song',
            $this->browseFactory,
            $gatekeeper->getUser(),
            AmpConfig::get_web_path(),
            canEditArtist($artist, $gatekeeper->getUserId()),
            $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('artist'),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
        )->render();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
