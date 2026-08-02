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

namespace Ampache\Module\Application\Video;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'confirm_delete';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        $video = new Video(
            (int) filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_SPECIAL_CHARS)
        );
        if (!Catalog::can_remove($video)) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the video `%s`', $video->id),
            );
        }

        // A video has no parent object, so leaving its own page can only fall back to the video browser.
        $webPath     = $this->configContainer->getWebPath();
        $burlParam   = (string) ($request->getQueryParams()['burl'] ?? '');
        $continueUrl = $this->deletionUrlResolver->resolveContinueUrl(
            $this->deletionUrlResolver->resolveBurl($burlParam),
            'video_id',
            $video->id,
            '',
            sprintf('%s/browse.php?action=video', $webPath)
        );

        $this->ui->showHeader();

        if ($video->remove()) {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('Video has been deleted'),
                $continueUrl
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
                /* HINT: Artist, Album, Song, Catalog, Video, Catalog Filter */
                sprintf(T_("Couldn't delete this %s"), T_('Video')),
                $continueUrl
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
