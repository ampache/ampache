<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Utility;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

/**
 * This is a little bit of a special file, it takes the
 * content of $_SESSION['iframe']['target'] and does a header
 * redirect to that spot!
 */
final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Expires',
                'Tuesday, 27 Mar 1984 05:00:00 GMT'
            )
            ->withHeader(
                'Last-Modified',
                gmdate('D, d M Y H:i:s') . " GMT"
            )
            ->withHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate'
            )
            ->withHeader(
                'Pragma',
                'no-cache'
            );

        if (isset($_SESSION) && array_key_exists('iframe', $_SESSION) && array_key_exists('target', $_SESSION['iframe'])) {
            $target = $_SESSION['iframe']['target'];
            unset($_SESSION['iframe']['target']);

            $response = $response->withHeader(
                'Location',
                $target
            )->withStatus(StatusCode::FOUND);
        } else {
            // Prevent the update query as it's pointless
            define('NO_SESSION_UPDATE', '1');
        }

        return $response;
    }
}
