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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Artist\ArtistPageView;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private AlbumRepositoryInterface $albumRepository,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $artistId  = (int) ($queryParams['artist'] ?? 0);
        $catalogId = $queryParams['catalog'] ?? null;
        if ($catalogId !== null) {
            $catalogId = (int) $catalogId;
        }

        $artist = $this->modelFactory->createArtist($artistId);

        $shown = !$artist->isNew() && $artist->isVisible($gatekeeper->getUser());
        if ($shown) {
            $webPath = AmpConfig::get_web_path('/client');
            $url     = $webPath . '/artists.php?action=show&artist=' . $artistId;
            PageMeta::set(
                [
                    (string) $artist->placeformed,
                    ($artist->album_count > 0) ? sprintf(nT_('%d album', '%d albums', $artist->album_count), $artist->album_count) : null,
                    ($artist->song_count > 0) ? sprintf(nT_('%d song', '%d songs', $artist->song_count), $artist->song_count) : null,
                    $artist->get_f_tags(),
                ],
                'profile',
                (string) $artist->get_fullname(),
                $url,
                $webPath . '/image.php?object_id=' . $artistId . '&object_type=artist&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MusicGroup',
                    'name' => (string) $artist->get_fullname(),
                    'url' => $url,
                    'genre' => array_values(array_filter(array_map(static fn(array $tag): string => (string) $tag['name'], $artist->get_tags()))),
                    'foundingDate' => ($artist->yearformed !== null && $artist->yearformed > 0) ? (string) $artist->yearformed : null,
                    'foundingLocation' => ((string) $artist->placeformed !== '') ? ['@type' => 'Place', 'name' => (string) $artist->placeformed] : null,
                ])
            );
        }

        $this->ui->showHeader();

        if (!$shown) {
            $this->logger->warning(
                sprintf(
                    'Refused artist %d: %s',
                    $artistId,
                    ($artist->isNew()) ? 'no such artist' : 'withdrawn'
                ),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP)) {
                $objectType = 'album';
            } else {
                $objectType = 'album_disk';
            }

            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_RELEASE_TYPE)) {
                // grouped by release type, each list carrying the heading it renders under
                /** @var array<string, list<int>> $multi_object_ids */
                $multi_object_ids = $this->albumRepository->getByArtist($artistId, $catalogId, true);
            } else {
                /** @var list<int> $object_ids */
                $object_ids       = $this->albumRepository->getByArtist($artistId, $catalogId);
                $multi_object_ids = ['' => $object_ids];
            }

            echo new ArtistPageView(
                $artist,
                $multi_object_ids,
                $objectType,
                $this->browseFactory,
                $gatekeeper->getUser(),
                AmpConfig::get_web_path('/client'),
                canEditArtist($artist, $gatekeeper->getUserId()),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('artist'),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            )->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
