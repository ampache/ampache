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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DemocraticAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'democratic';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;

        parent::__construct($logger, $configContainer);
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }

        $democratic_id = (int)$this->requestParser->getFromRequest('democratic_id');
        $democratic    = $this->modelFactory->createDemocratic($democratic_id);
        $urls          = [$democratic->play_url()];
        $play_type     = $this->configContainer->get(ConfigurationKeyEnum::PLAY_TYPE);
        $stream_type   = ($play_type == 'democratic')
            ? $this->configContainer->get(ConfigurationKeyEnum::PLAYLIST_TYPE)
            : $play_type;

        return $this->stream(
            [],
            $urls,
            $stream_type
        );
    }
}
