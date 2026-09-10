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

final readonly class ShowDiskAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_disk';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private FunctionCheckerInterface $functionChecker,
        private AlbumEditabilityCheckerInterface $editabilityChecker,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user        = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $catalogs    = $user->catalogs['music'] ?? User::get_user_catalogs($user->id);
        $albumDiskId = (int) ($request->getQueryParams()['album_disk'] ?? 0);
        $albumDisk   = $this->modelFactory->createAlbumDisk($albumDiskId);
        $shown       = !$albumDisk->isNew() && in_array($albumDisk->catalog, $catalogs) && $albumDisk->isVisible($user);

        if ($shown) {
            $webPath = AmpConfig::get_web_path();
            $url     = $webPath . '/albums.php?action=show_disk&album_disk=' . $albumDiskId;
            PageMeta::set(
                [
                    $albumDisk->get_parent_fullname(),
                    ($albumDisk->year > 0) ? $albumDisk->year : null,
                    ($albumDisk->song_count > 0) ? sprintf(nT_('%d song', '%d songs', $albumDisk->song_count), $albumDisk->song_count) : null,
                    $albumDisk->get_f_time(),
                ],
                'music.album',
                $albumDisk->get_fullname(),
                $url,
                $webPath . '/image.php?object_id=' . $albumDisk->album_id . '&object_type=album&size=600x600',
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MusicAlbum',
                    'name' => $albumDisk->get_fullname(),
                    'url' => $url,
                    'byArtist' => ['@type' => 'MusicGroup', 'name' => $albumDisk->get_parent_fullname()],
                    'numTracks' => $albumDisk->song_count,
                ])
            );
        }

        $this->ui->showHeader();

        if (!$shown) {
            $this->logger->warning(
                'Requested an album_disk that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            echo new AlbumPageView(
                $albumDisk,
                $this->browseFactory,
                $gatekeeper->getUser(),
                AmpConfig::get_web_path(),
                $this->editabilityChecker->check($gatekeeper, $albumDisk),
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('album_disk'),
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
