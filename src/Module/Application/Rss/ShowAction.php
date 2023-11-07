<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Rss;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AmpacheRss;
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Check Perms */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USE_RSS) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
        ) {
            return null;
        }

        $type     = $this->requestParser->getFromRequest('type');
        $rsstoken = $this->requestParser->getFromRequest('rsstoken');
        $rss      = new AmpacheRss($type, $rsstoken);
        $params   = null;

        if ($type === 'podcast') {
            $params                = [];
            $params['object_type'] = $this->requestParser->getFromRequest('object_type');
            $params['object_id']   = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($params['object_id'])) {
                return null;
            }
        }

        return $this->responseFactory->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf(
                    'application/xml; charset=%s',
                    $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET)
                )
            )
            ->withBody(
                $this->streamFactory->createStream($rss->get_xml($params))
            );
    }
}
