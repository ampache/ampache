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
use Ampache\Module\Album\Deletion\AlbumDeleterInterface;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
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

final readonly class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'confirm_delete';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private AlbumDeleterInterface $albumDeleter,
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

        $album_id = (int) $this->requestParser->getFromRequest('album_id');
        $album    = $this->modelFactory->createAlbum($album_id);
        if (!Catalog::can_remove($album)) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the album `%d`', $album->id)
            );
        }

        // The album artist has to be read while the row is still there; the deleter takes the album with it,
        $webPath   = $this->configContainer->getWebPath('/client');
        $parentUrl = ($album->album_artist !== null && $album->album_artist > 0)
            ? sprintf('%s/artists.php?action=show&artist=%d', $webPath, $album->album_artist)
            : '';
        $burlParam   = (string) ($request->getQueryParams()['burl'] ?? '');
        $continueUrl = $this->deletionUrlResolver->resolveContinueUrl(
            $this->deletionUrlResolver->resolveBurl($burlParam),
            'album',
            $album_id,
            $parentUrl,
            sprintf('%s/browse.php?action=album', $webPath)
        );

        $this->ui->showHeader();
        try {
            $this->albumDeleter->delete($album);

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Album has been deleted'),
                $continueUrl
            );
        } catch (AlbumDeletionException) {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: Artist, Album, Song, Catalog, Video, Catalog Filter */
                sprintf(T_("Couldn't delete this %s"), T_('Album')),
                $continueUrl
            );
        }

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
