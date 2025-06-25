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

namespace Ampache\Module\Application\Rss;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Rss\RssFeedTypeFactoryInterface;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Song;
use Ampache\Repository\UserRepositoryInterface;
use PhpTal\PHPTAL;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ResponseFactoryInterface $responseFactory,
        private UserRepositoryInterface $userRepository,
        private TalFactoryInterface $talFactory,
        private RssFeedTypeFactoryInterface $rssFeedTypeFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
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

        $queryParams = $request->getQueryParams();

        $type     = RssFeedTypeEnum::tryFrom($queryParams['type'] ?? '') ?? RssFeedTypeEnum::NOW_PLAYING;
        $rssToken = $queryParams['rsstoken'] ?? '';

        $user = $this->userRepository->getByRssToken($rssToken);

        if ($type === RssFeedTypeEnum::LIBRARY_ITEM) {
            $item = $this->libraryItemLoader->load(
                LibraryItemEnum::from($queryParams['object_type'] ?? ''),
                (int) ($queryParams['object_id'] ?? 0),
                [Album::class, Artist::class, Podcast::class, Song::class]
            );

            if ($item === null) {
                return null;
            }

            $handler = $this->rssFeedTypeFactory->createLibraryItemFeed($user, $item);
        } else {
            $handler = match ($type) {
                default => $this->rssFeedTypeFactory->createNowPlayingFeed(),
                RssFeedTypeEnum::RECENTLY_PLAYED => $this->rssFeedTypeFactory->createRecentlyPlayedFeed($user),
                RssFeedTypeEnum::LATEST_ALBUM => $this->rssFeedTypeFactory->createLatestAlbumFeed($user, $request),
                RssFeedTypeEnum::LATEST_ARTIST => $this->rssFeedTypeFactory->createLatestArtistFeed($user, $request),
                RssFeedTypeEnum::LATEST_SHOUT => $this->rssFeedTypeFactory->createLatestShoutFeed(),
                RssFeedTypeEnum::LATEST_SONG => $this->rssFeedTypeFactory->createLatestSongFeed($user, $request),
            };
        }

        $tal = $this->talFactory->createPhpTal();
        $tal->setOutputMode(PHPTAL::XML);
        $tal->setEncoding(AmpConfig::get('site_charset', 'UTF-8'));

        $handler->configureTemplate($tal);

        $response = $this->responseFactory->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf(
                    'application/xml; charset=%s',
                    $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET)
                )
            );

        $response->getBody()->write($tal->execute());

        return $response;
    }
}
