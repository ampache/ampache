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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Album\Deletion\AlbumDiskDeleterInterface;
use Ampache\Module\Album\Deletion\Exception\AlbumDiskDeletionException;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmDeleteDiskAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'confirm_delete_disk';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private AlbumDiskDeleterInterface $albumDiskDeleter,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            // Show the Footer
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        if ($this->requestParser->verifyForm('delete_album_disk') === false) {
            throw new AccessDeniedException();
        }

        $albumDiskId = (int) $this->requestParser->getFromRequest('album_disk');
        $albumDisk   = $this->modelFactory->createAlbumDisk($albumDiskId);
        if (!Catalog::can_remove($albumDisk)) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the album disk `%d`', $albumDiskId)
            );
        }

        // the album and its artist have to be read while the disk row is still there; deleting the
        // last disk takes the album with it, so the page to land on afterwards has to be chosen now
        $webPath      = $this->configContainer->getWebPath();
        $album        = $this->modelFactory->createAlbum($albumDisk->getAlbumId());
        $albumPageUrl = sprintf('%s/albums.php?action=show&album=%d', $webPath, $album->getId());
        $artistUrl    = ($album->album_artist !== null && $album->album_artist > 0)
            ? sprintf('%s/artists.php?action=show&artist=%d', $webPath, $album->album_artist)
            : '';
        $burl = $this->deletionUrlResolver->resolveBurl((string) ($request->getQueryParams()['burl'] ?? ''));

        $this->ui->showHeader();
        try {
            $albumDeleted = $this->albumDiskDeleter->delete($albumDisk);

            // the album's own page is gone too once the disk was its last one
            $parentUrl   = ($albumDeleted) ? $artistUrl : $albumPageUrl;
            $continueUrl = $this->deletionUrlResolver->resolveContinueUrl(
                ($albumDeleted && $burl === $albumPageUrl) ? '' : $burl,
                'album_disk',
                $albumDiskId,
                $parentUrl,
                sprintf('%s/browse.php?action=album', $webPath)
            );

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Disk has been deleted'),
                $continueUrl
            );
        } catch (AlbumDiskDeletionException) {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: Artist, Album, Song, Catalog, Video, Catalog Filter, Disk */
                sprintf(T_("Couldn't delete this %s"), T_('Disk')),
                $albumPageUrl
            );
        }

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
