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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private AlbumRepositoryInterface $albumRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        AlbumRepositoryInterface $albumRepository
    ) {
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->logger          = $logger;
        $this->albumRepository = $albumRepository;
    }

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
        $artist->format();

        if ($artist->isNew()) {
            $this->logger->warning(
                'Requested an artist that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an Artist that does not exist.');
        } else {
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_RELEASE_TYPE) === true) {
                $multi_object_ids = $this->albumRepository->getByArtist(
                    $artistId,
                    $catalogId,
                    true
                );
                $object_ids = null;
            } else {
                $object_ids = $this->albumRepository->getByArtist(
                    $artistId,
                    $catalogId
                );
                $multi_object_ids = null;
            }

            $this->ui->show(
                'show_artist.inc.php',
                [
                    'multi_object_ids' => $multi_object_ids,
                    'object_ids' => $object_ids,
                    'object_type' => 'album',
                    'artist' => $artist,
                    'gatekeeper' => $gatekeeper,
                ]
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
