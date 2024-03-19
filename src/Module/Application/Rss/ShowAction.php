<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Rss;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Rss\AmpacheRssInterface;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private AmpacheRssInterface $ampacheRss,
        private UserRepositoryInterface $userRepository,
    ) {
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

        $type     = RssFeedTypeEnum::tryFrom($this->requestParser->getFromRequest('type')) ?? RssFeedTypeEnum::NOW_PLAYING;
        $rssToken = $this->requestParser->getFromRequest('rsstoken');

        if ($type === RssFeedTypeEnum::LIBRARY_ITEM) {
            $params                = [];
            $params['object_type'] = $this->requestParser->getFromRequest('object_type');
            $params['object_id']   = (int) $this->requestParser->getFromRequest('object_id');
            if (empty($params['object_id'])) {
                return null;
            }
        } else {
            $params = null;
        }

        $user = $this->userRepository->getByRssToken($rssToken);
        if ($user === null) {
            $user = new User(User::INTERNAL_SYSTEM_USER_ID);
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
                $this->streamFactory->createStream(
                    $this->ampacheRss->get_xml(
                        $user,
                        $type,
                        $params
                    )
                )
            );
    }
}
