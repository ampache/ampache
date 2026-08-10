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

namespace Ampache\Module\Application\Playback;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

/**
 * The `play2` action was a preference gated alternative to PlayAction and has been merged into it and removed.
 * Redirect any lingering `action=play2` links (bookmarked/cached stream URLs to the equivalent `play` action instead of failing.
 */
final readonly class Play2RedirectAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'play2';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ConfigContainerInterface $configContainer,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        $params           = $request->getQueryParams();
        $params['action'] = PlayAction::REQUEST_KEY;

        return $this->responseFactory
            ->createResponse(RFC7231::MOVED_PERMANENTLY)
            ->withHeader(
                'Location',
                $this->configContainer->getWebPath() . '/play/index.php?' . http_build_query($params)
            );
    }
}
