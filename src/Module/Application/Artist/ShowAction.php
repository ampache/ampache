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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Artist\ArtistPageView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private AlbumRepositoryInterface $albumRepository,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $queryParams = $request->getQueryParams();

        $artistId  = (int) ($queryParams['artist'] ?? 0);
        $catalogId = $queryParams['catalog'] ?? null;
        if ($catalogId !== null) {
            $catalogId = (int) $catalogId;
        }

        $artist = $this->modelFactory->createArtist($artistId);

        if ($artist->isNew()) {
            $this->logger->warning(
                'Requested an artist that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP)) {
                $objectType = 'album';
            } else {
                $objectType = 'album_disk';
            }

            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_RELEASE_TYPE)) {
                // grouped by release type, each list carrying the heading it renders under
                /** @var array<string, list<int>> $multi_object_ids */
                $multi_object_ids = $this->albumRepository->getByArtist($artistId, $catalogId, true);
            } else {
                /** @var list<int> $object_ids */
                $object_ids       = $this->albumRepository->getByArtist($artistId, $catalogId);
                $multi_object_ids = ['' => $object_ids];
            }

            echo (new ArtistPageView(
                $artist,
                $multi_object_ids,
                $objectType,
                $this->browseFactory,
                $gatekeeper->getUser(),
                AmpConfig::get_web_path(),
                canEditArtist($artist, $gatekeeper->getUserId()),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('artist'),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ))->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
