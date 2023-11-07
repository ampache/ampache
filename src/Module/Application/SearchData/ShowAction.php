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

namespace Ampache\Module\Application\SearchData;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->requestParser   = $requestParser;
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $search = $this->modelFactory->createSearch(
            null,
            $this->requestParser->getFromRequest('type')
        );

        $content = 'var types = ';
        $content .= $this->arrayToJSON($search->types) . ";\n";
        $content .= 'var basetypes = ';
        $content .= $this->arrayToJSON($search->basetypes) . ";\n";
        $content .= sprintf(
            'removeIcon = \'<a href="javascript: void(0)">%s</a>\';',
            Ui::get_icon('disable', T_('Remove'))
        );

        return $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-Type',
                'application/x-javascript'
            )
            ->withBody(
                $this->streamFactory->createStream($content)
            );
    }

    /**
     * @deprecated json_encode should do the trick here
     */
    private function arrayToJSON($array): string
    {
        $json = '{ ';
        foreach ($array as $key => $value) {
            $json .= '"' . $key . '" : ';
            if (is_array($value)) {
                $json .= $this->arrayToJSON($value);
            } else {
                // Make sure to strip backslashes and convert things to
                // entities in our output
                $json .= '"' . scrub_out(str_replace(['"', '\\'], '', $value)) . '"';
            }
            $json .= ' , ';
        }
        $json = rtrim((string) $json, ', ');

        return $json . ' }';
    }
}
