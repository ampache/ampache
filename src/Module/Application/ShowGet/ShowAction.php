<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Application\ShowGet;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Looks like some kind of debugging tool?
 * @deprecated maybe obsolete
 */
final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestParser   = $requestParser;
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $content = '';

        if (array_key_exists('param_name', $_REQUEST)) {
            $name = $this->requestParser->getFromRequest('param_name');
            if (array_key_exists($name, $_REQUEST)) {
                $content .= sprintf(
                    '%s: %s',
                    $name,
                    $this->requestParser->getFromRequest('name')
                );
            }
        }

        if (array_key_exists('error', $_REQUEST)) {
            $error             = $this->requestParser->getFromRequest('error');
            $error_description = $this->requestParser->getFromRequest('error_description');
            $content .= sprintf('%s error: %s', $error, $error_description);
        }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream($content)
            );
    }
}
