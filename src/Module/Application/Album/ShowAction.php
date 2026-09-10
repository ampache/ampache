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

namespace Ampache\Module\Application\Album;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Album\AlbumPageView;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Module\Album\Edit\AlbumEditabilityCheckerInterface;
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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private ConfigContainerInterface $configContainer,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private AlbumEditabilityCheckerInterface $editabilityChecker,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user     = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $catalogs = $user->catalogs['music'] ?? User::get_user_catalogs($user->id);
        $albumId  = (int) ($request->getQueryParams()['album'] ?? 0);
        $album    = $this->modelFactory->createAlbum($albumId);
        $shown    = !$album->isNew() && ($album->catalog === 0 || in_array($album->catalog, $catalogs)) && $album->isVisible($user);

        if ($shown) {
            $webPath = AmpConfig::get_web_path();
            $url     = $webPath . '/albums.php?action=show&album=' . $albumId;
            PageMeta::set(
                [
                    $album->get_parent_fullname(),
                    ($album->year > 0) ? $album->year : null,
                    ($album->song_count > 0) ? sprintf(nT_('%d song', '%d songs', $album->song_count), $album->song_count) : null,
                    $album->get_f_time(),
                    $album->get_f_tags(),
                ],
                'music.album',
                $album->get_fullname(),
                $url,
                $webPath . '/image.php?object_id=' . $albumId . '&object_type=album&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MusicAlbum',
                    'name' => $album->get_fullname(),
                    'url' => $url,
                    'byArtist' => ['@type' => 'MusicGroup', 'name' => $album->get_parent_fullname()],
                    'numTracks' => $album->song_count,
                    'datePublished' => ($album->year > 0) ? (string) $album->year : null,
                ])
            );
        }

        $this->ui->showHeader();

        if (!$shown) {
            $this->logger->warning(
                sprintf(
                    'Refused album %d: %s',
                    $albumId,
                    ($album->isNew()) ? 'no such album' : 'withdrawn, or outside the catalogues this user may see'
                ),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } elseif ($album->getDiskCount() > 1) {
            // Multi disk albums
            echo new AlbumPageView(
                $album,
                $this->browseFactory,
                $gatekeeper->getUser(),
                $this->configContainer->getWebPath(),
                $this->editabilityChecker->check($gatekeeper, $album),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('album_disk'),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
                true
            )->render();
        } else {
            // Single disk albums
            echo new AlbumPageView(
                $album,
                $this->browseFactory,
                $gatekeeper->getUser(),
                $this->configContainer->getWebPath(),
                $this->editabilityChecker->check($gatekeeper, $album),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('album'),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            )->render();
        }

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
