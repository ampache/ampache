<?php

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

declare(strict_types=0);

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ArtistRandomAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'artist_random';

    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        SongRepositoryInterface $songRepository
    ) {
        parent::__construct($logger, $configContainer);
        $this->requestParser   = $requestParser;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->songRepository  = $songRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }

        $artist_id = (int)$this->requestParser->getFromRequest('artist_id');
        $artist    = $this->modelFactory->createArtist($artist_id);

        return $this->stream(
            $this->songRepository->getRandomByArtist($artist),
            [],
            $this->configContainer->get(ConfigurationKeyEnum::PLAY_TYPE)
        );
    }
}
