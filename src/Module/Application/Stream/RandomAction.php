<?php

/*
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
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Random;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class RandomAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'random';

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        $this->logger          = $logger;
        $this->configContainer = $configContainer;

        parent::__construct($logger, $configContainer);
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }
        $randomId   = (int)($request->getQueryParams()['random_id'] ?? 0);
        $randomType = $request->getQueryParams()['random_type'];
        $urls       = [Random::get_play_url($randomType, $randomId)];

        return $this->stream(
            [],
            $urls,
            $this->configContainer->get(ConfigurationKeyEnum::PLAY_TYPE)
        );
    }
}
