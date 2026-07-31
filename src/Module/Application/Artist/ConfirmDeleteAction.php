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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Artist\Deletion\ArtistDeleterInterface;
use Ampache\Module\Artist\Deletion\Exception\ArtistDeletionException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'confirm_delete';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private ModelFactoryInterface $modelFactory,
        private ArtistDeleterInterface $artistDeleter,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showHeader();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $artist_id = (int) $this->requestParser->getFromRequest('artist_id');
        $artist    = $this->modelFactory->createArtist($artist_id);

        if (!Catalog::can_remove($artist)) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the artist `%d`', $artist->id)
            );
        }

        // An artist has no parent object, so leaving its own page can only fall back to the artist browser.
        $webPath     = $this->configContainer->getWebPath();
        $burlParam   = (string) ($request->getQueryParams()['burl'] ?? '');
        $continueUrl = $this->deletionUrlResolver->resolveContinueUrl(
            $this->deletionUrlResolver->resolveBurl($burlParam),
            'artist',
            $artist_id,
            '',
            sprintf('%s/browse.php?action=artist', $webPath)
        );

        $this->ui->showHeader();
        try {
            $this->artistDeleter->remove($artist);
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Artist has been deleted'),
                $continueUrl
            );
        } catch (ArtistDeletionException) {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: Artist, Album, Song, Catalog, Video, Catalog Filter */
                sprintf(T_("Couldn't delete this %s"), T_('Artist')),
                $continueUrl
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
